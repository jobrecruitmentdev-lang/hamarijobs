import requests
import json
import pymysql

BASE_URL = "http://127.0.0.1:8080"
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'job_recruitment_ai',
    'cursorclass': pymysql.cursors.DictCursor
}

def get_db():
    return pymysql.connect(**DB_CONFIG)

def run_tests():
    session = requests.Session()
    print("=== Starting End-to-End Admin CRUD & Sync Tests ===")

    # 1. Login to Admin
    login_res = session.post(f"{BASE_URL}/api/v1/admin/login", json={
        "email": "admin@jobrecruitai.com",
        "password": "Admin@123"
    })
    assert login_res.status_code == 200, f"Login failed: {login_res.text}"
    login_data = login_res.json()
    assert login_data.get('success') is True, f"Login unsuccessful: {login_data}"
    csrf_token = login_data.get('csrf_token')
    if csrf_token:
        session.headers.update({'X-CSRF-Token': csrf_token})
    print("[PASS] Admin Login authenticated successfully with CSRF protection.")

    # 2. Test Recruitments (Jobs) CRUD
    print("\n--- Testing Recruitments CRUD ---")
    create_job_res = session.post(f"{BASE_URL}/api/v1/admin/recruitments/create", json={
        "title": "E2E Test Recruitment 2026",
        "organization_name": "Test Public Service Board",
        "advertisement_number": "E2E/2026/01",
        "total_vacancies": 500,
        "qualification_level": "Bachelor's Degree",
        "state_code": "ALL",
        "status": "Active",
        "summary": "E2E test summary overview",
        "official_apply_url": "https://gov.in/apply",
        "primary_notification_url": "https://gov.in/notice.pdf",
        "pay_scale": "Rs. 44,900 - 1,42,400",
        "age_limit": "20 to 30 Years",
        "fee_details": "Rs. 100",
        "start_date": "2026-03-01",
        "last_date": "2026-03-31"
    })
    assert create_job_res.status_code == 200, f"Create job failed: {create_job_res.text}"
    job_id = create_job_res.json().get('id')
    print(f"[PASS] Created Job #{job_id}")

    # Verify in DB
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM recruitments WHERE id = %s", (job_id,))
            job_row = cur.fetchone()
            assert job_row is not None, "Job not found in DB after create"
            assert job_row['total_vacancies'] == 500

    # Edit Job
    edit_job_res = session.post(f"{BASE_URL}/api/v1/admin/recruitments/update", json={
        "id": job_id,
        "title": "E2E Test Recruitment 2026 (Updated)",
        "organization_name": "Test Public Service Board",
        "total_vacancies": 750,
        "qualification_level": "Master's Degree",
        "status": "Active"
    })
    assert edit_job_res.status_code == 200, f"Edit job failed: {edit_job_res.text}"
    print(f"[PASS] Edited Job #{job_id}")

    # Delete Job (tests deleteJob controller with cascade on fact_claims and events)
    del_job_res = session.post(f"{BASE_URL}/api/v1/admin/recruitments/delete", json={"id": job_id})
    assert del_job_res.status_code == 200, f"Delete job failed: {del_job_res.text}"
    del_job_data = del_job_res.json()
    assert del_job_data.get('success') is True, f"Delete job error: {del_job_data}"
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM recruitments WHERE id = %s", (job_id,))
            assert cur.fetchone() is None, "Job still exists in DB after delete"
            cur.execute("SELECT * FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = %s", (job_id,))
            assert cur.fetchone() is None, "Fact claims not cascade cleaned"
    print(f"[PASS] Deleted Job #{job_id} cleanly with cascade verification.")

    # 3. Test Commissions CRUD & Frontend Sync
    print("\n--- Testing Commissions CRUD & Frontend Sync ---")
    create_comm_res = session.post(f"{BASE_URL}/api/v1/admin/commissions/create", json={
        "name": "E2E Test Constitutional Commission",
        "short_name": "ETCC",
        "slug": "e2e-test-comm",
        "emblem": "landmark",
        "category": "Central Autonomous Body",
        "annual_candidates": "500K+ Candidates",
        "filter_keyword": "ETCC",
        "is_active": 1
    })
    assert create_comm_res.status_code == 200, f"Create commission failed: {create_comm_res.text}"
    comm_id = create_comm_res.json().get('id')
    print(f"[PASS] Created Commission #{comm_id} (slug: e2e-test-comm)")

    # Verify Frontend Homepage renders newly created commission dynamically
    home_page_res = requests.get(f"{BASE_URL}/")
    assert home_page_res.status_code == 200
    assert "e2e-test-comm" in home_page_res.text, "Newly created commission not visible on homepage!"
    assert "ETCC" in home_page_res.text, "Commission short name not visible on homepage!"
    print("[PASS] Verified newly created Commission is dynamically visible on Homepage grid!")

    # Edit Commission
    edit_comm_res = session.post(f"{BASE_URL}/api/v1/admin/commissions/update", json={
        "id": comm_id,
        "name": "E2E Test Constitutional Commission (Updated)",
        "short_name": "ETCC-UP",
        "slug": "e2e-test-comm",
        "emblem": "shield",
        "annual_candidates": "800K+ Candidates",
        "is_active": 1
    })
    assert edit_comm_res.status_code == 200, f"Edit commission failed: {edit_comm_res.text}"
    print(f"[PASS] Edited Commission #{comm_id}")

    # Delete Commission
    del_comm_res = session.post(f"{BASE_URL}/api/v1/admin/commissions/delete", json={"id": comm_id})
    assert del_comm_res.status_code == 200, f"Delete commission failed: {del_comm_res.text}"
    assert del_comm_res.json().get('success') is True
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM commissions WHERE id = %s", (comm_id,))
            assert cur.fetchone() is None, "Commission still exists in DB after delete"
    print(f"[PASS] Deleted Commission #{comm_id} cleanly.")

    # 4. Test Exam Hubs CRUD (with FK cascade cleanup)
    print("\n--- Testing Exam Hubs CRUD (Cascade Cleanup) ---")
    create_exam_res = session.post(f"{BASE_URL}/api/v1/admin/exams/create", json={
        "name": "E2E Test National Recruitment Exam",
        "short_name": "ETNRE",
        "slug": "etnre-2026",
        "conducting_body": "National Recruitment Agency",
        "category": "Central",
        "frequency": "Annual",
        "overview": "E2E exam overview description",
        "is_active": 1
    })
    assert create_exam_res.status_code == 200, f"Create exam failed: {create_exam_res.text}"
    exam_id = create_exam_res.json().get('id')
    print(f"[PASS] Created Exam Hub #{exam_id}")

    # Insert a child cutoff record and exam phase to simulate MySQL 1451 FK constraint
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("INSERT INTO cutoff_records (exam_id, year, category, cutoff_marks, total_marks, created_at) VALUES (%s, 2026, 'UR', 150.0, 200.0, NOW())", (exam_id,))
            cur.execute("INSERT INTO exam_phases (exam_id, phase_name, phase_order, created_at) VALUES (%s, 'Preliminary Examination', 1, NOW())", (exam_id,))
            conn.commit()
    print("[INFO] Inserted child cutoff_records & exam_phases referencing this Exam.")

    # Edit Exam
    edit_exam_res = session.post(f"{BASE_URL}/api/v1/admin/exams/update", json={
        "id": exam_id,
        "name": "E2E Test National Recruitment Exam (Updated)",
        "short_name": "ETNRE-2",
        "conducting_body": "National Recruitment Agency",
        "category": "Central",
        "is_active": 1
    })
    assert edit_exam_res.status_code == 200, f"Edit exam failed: {edit_exam_res.text}"
    print(f"[PASS] Edited Exam Hub #{exam_id}")

    # Delete Exam (must cascade delete child cutoff and phases without 1451 error)
    del_exam_res = session.post(f"{BASE_URL}/api/v1/admin/exams/delete", json={"id": exam_id})
    assert del_exam_res.status_code == 200, f"Delete exam failed: {del_exam_res.text}"
    del_exam_data = del_exam_res.json()
    assert del_exam_data.get('success') is True, f"Delete exam error: {del_exam_data}"
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM exams WHERE id = %s", (exam_id,))
            assert cur.fetchone() is None, "Exam still in DB"
            cur.execute("SELECT * FROM cutoff_records WHERE exam_id = %s", (exam_id,))
            assert cur.fetchone() is None, "Child cutoffs not cascaded"
            cur.execute("SELECT * FROM exam_phases WHERE exam_id = %s", (exam_id,))
            assert cur.fetchone() is None, "Child phases not cascaded"
    print(f"[PASS] Deleted Exam Hub #{exam_id} with complete FK cascade cleanup!")

    # 5. Test Events (Admit Cards / Results) CRUD
    print("\n--- Testing Events & Notices CRUD ---")
    create_ev_res = session.post(f"{BASE_URL}/api/v1/admin/events/create", json={
        "event_title": "E2E Test Admit Card Download Notification",
        "organization_name": "Test Recruiting Board",
        "event_type": "ADMIT_CARD_RELEASED",
        "event_date": "2026-04-15",
        "is_tentative": 0,
        "reference_url": "https://gov.in/admit-card"
    })
    assert create_ev_res.status_code == 200, f"Create event failed: {create_ev_res.text}"
    ev_id = create_ev_res.json().get('id')
    print(f"[PASS] Created Event Notice #{ev_id}")

    # Edit Event
    edit_ev_res = session.post(f"{BASE_URL}/api/v1/admin/events/update", json={
        "id": ev_id,
        "event_title": "E2E Test Admit Card Download Notification (Updated)",
        "organization_name": "Test Recruiting Board",
        "event_type": "ADMIT_CARD_RELEASED",
        "event_date": "2026-04-20"
    })
    assert edit_ev_res.status_code == 200, f"Edit event failed: {edit_ev_res.text}"
    print(f"[PASS] Edited Event Notice #{ev_id}")

    # Delete Event
    del_ev_res = session.post(f"{BASE_URL}/api/v1/admin/events/delete", json={"id": ev_id})
    assert del_ev_res.status_code == 200, f"Delete event failed: {del_ev_res.text}"
    assert del_ev_res.json().get('success') is True
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM recruitment_events WHERE id = %s", (ev_id,))
            assert cur.fetchone() is None, "Event still in DB"
    print(f"[PASS] Deleted Event Notice #{ev_id}")

    # 6. Test Cutoffs CRUD
    print("\n--- Testing Cutoff Records CRUD ---")
    # Fetch an existing exam for FK
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id FROM exams LIMIT 1")
            first_exam = cur.fetchone()
            assert first_exam is not None, "No exams exist in DB"
            target_exam_id = first_exam['id']

    create_cut_res = session.post(f"{BASE_URL}/api/v1/admin/cutoffs/create", json={
        "exam_id": target_exam_id,
        "year": 2026,
        "category": "EWS",
        "cutoff_marks": 138.75,
        "total_marks": 200.00,
        "qualifying_candidates": 4200,
        "official_notice_url": "https://gov.in/cutoff-notice.pdf"
    })
    assert create_cut_res.status_code == 200, f"Create cutoff failed: {create_cut_res.text}"
    cut_id = create_cut_res.json().get('id')
    print(f"[PASS] Created Cutoff Record #{cut_id}")

    # Get Cutoff
    get_cut_res = session.get(f"{BASE_URL}/api/v1/admin/cutoffs/get?id={cut_id}")
    assert get_cut_res.status_code == 200, f"Get cutoff failed: {get_cut_res.text}"
    assert get_cut_res.json().get('data', {}).get('category') == 'EWS'
    print(f"[PASS] Fetched Cutoff Record #{cut_id} details via API")

    # Update Cutoff
    update_cut_res = session.post(f"{BASE_URL}/api/v1/admin/cutoffs/update", json={
        "id": cut_id,
        "exam_id": target_exam_id,
        "year": 2026,
        "category": "EWS",
        "cutoff_marks": 141.25,
        "total_marks": 200.00,
        "qualifying_candidates": 3800
    })
    assert update_cut_res.status_code == 200, f"Update cutoff failed: {update_cut_res.text}"
    assert update_cut_res.json().get('success') is True
    print(f"[PASS] Updated Cutoff Record #{cut_id} via API")

    # Delete Cutoff
    del_cut_res = session.post(f"{BASE_URL}/api/v1/admin/cutoffs/delete", json={"id": cut_id})
    assert del_cut_res.status_code == 200, f"Delete cutoff failed: {del_cut_res.text}"
    assert del_cut_res.json().get('success') is True
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM cutoff_records WHERE id = %s", (cut_id,))
            assert cur.fetchone() is None, "Cutoff still in DB"
    print(f"[PASS] Deleted Cutoff Record #{cut_id}")

    # 7. Test Articles CRUD (with article_versions cascade)
    print("\n--- Testing Articles CRUD (Cascade Cleanup) ---")
    create_art_res = session.post(f"{BASE_URL}/api/v1/admin/articles/create", json={
        "title": "E2E Test Preparation Guide 2026",
        "article_type": "Preparation_Strategy",
        "excerpt": "A short test excerpt",
        "content": "Full article test content for E2E validation.",
        "reading_time_minutes": 7,
        "quality_score": 96
    })
    assert create_art_res.status_code == 200, f"Create article failed: {create_art_res.text}"
    art_id = create_art_res.json().get('id')
    print(f"[PASS] Created Article #{art_id}")

    # Insert a child version to test FK constraint
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("INSERT INTO article_versions (article_id, version_number, title, content, changed_summary, created_at) VALUES (%s, 1, 'Initial Title', 'Version 1 content', 'Admin', NOW())", (art_id,))
            conn.commit()
    print("[INFO] Inserted child article_versions row.")

    # Edit Article
    edit_art_res = session.post(f"{BASE_URL}/api/v1/admin/articles/update", json={
        "id": art_id,
        "title": "E2E Test Preparation Guide 2026 (Updated)",
        "article_type": "Preparation_Strategy",
        "content": "Updated full article content.",
        "reading_time_minutes": 8,
        "quality_score": 98
    })
    assert edit_art_res.status_code == 200, f"Edit article failed: {edit_art_res.text}"
    print(f"[PASS] Edited Article #{art_id}")

    # Delete Article (cascade cleanup of article_versions)
    del_art_res = session.post(f"{BASE_URL}/api/v1/admin/articles/delete", json={"id": art_id})
    assert del_art_res.status_code == 200, f"Delete article failed: {del_art_res.text}"
    assert del_art_res.json().get('success') is True
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM articles WHERE id = %s", (art_id,))
            assert cur.fetchone() is None, "Article still in DB"
            cur.execute("SELECT * FROM article_versions WHERE article_id = %s", (art_id,))
            assert cur.fetchone() is None, "Article version still in DB"
    print(f"[PASS] Deleted Article #{art_id} with complete FK cascade cleanup!")

    print("\n========================================================")
    print(" ALL 7 MODULES PASSED E2E CRUD & SYNC VALIDATION 100%!")
    print("========================================================")

if __name__ == "__main__":
    run_tests()
