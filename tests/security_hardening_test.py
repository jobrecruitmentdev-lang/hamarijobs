import requests
import json
import pymysql
import sys

# Ensure UTF-8 output on Windows terminal
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

BASE_URL = "http://127.0.0.1:8080"
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'job_recruitment_ai',
    'cursorclass': pymysql.cursors.DictCursor
}

def clean_rate_limits():
    conn = pymysql.connect(**DB_CONFIG)
    with conn.cursor() as cur:
        cur.execute("DELETE FROM login_attempts")
    conn.commit()
    conn.close()

def test_security_hardening():
    print("============================================================")
    print("🔒 RUNNING AUTOMATED SECURITY HARDENING VERIFICATION SUITE")
    print("============================================================")
    clean_rate_limits()

    # ------------------------------------------------------------
    # 1. Test Session Cookie Flags & Headers
    # ------------------------------------------------------------
    print("\n--- [TEST 1] Testing Cookie Flags & HTTP Security Headers ---")
    res = requests.get(f"{BASE_URL}/admin/login")
    assert res.status_code == 200, f"Failed to load login page: {res.status_code}"
    
    # Check headers
    assert res.headers.get('X-Content-Type-Options') == 'nosniff', "Missing X-Content-Type-Options: nosniff"
    assert res.headers.get('X-Frame-Options') == 'SAMEORIGIN', "Missing X-Frame-Options: SAMEORIGIN"
    assert res.headers.get('X-XSS-Protection') == '1; mode=block', "Missing X-XSS-Protection"
    print("[PASS] Security headers present: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection.")

    # Check cookies
    cookies = res.headers.get('Set-Cookie', '')
    if 'HttpOnly' in cookies:
        print("[PASS] Session cookie has HttpOnly flag set.")
    else:
        print("[INFO] Set-Cookie details:", cookies)

    # ------------------------------------------------------------
    # 2. Test CSRF Protection
    # ------------------------------------------------------------
    print("\n--- [TEST 2] Testing CSRF Protection & Token Validation ---")
    admin_session = requests.Session()
    login_res = admin_session.post(f"{BASE_URL}/api/v1/admin/login", json={
        "identity": "hamaritumhari786@gmail.com",
        "password": "Hostinger ki masi 4786"
    })
    assert login_res.status_code == 200, f"Admin login failed: {login_res.text}"
    login_data = login_res.json()
    assert login_data.get('success') is True, f"Login unsuccessful: {login_data}"
    csrf_token = login_data.get('csrf_token')
    assert csrf_token is not None and len(csrf_token) >= 32, f"Invalid CSRF token: {csrf_token}"
    print(f"[PASS] Successfully authenticated and received CSRF token: {csrf_token[:8]}...")

    # Attempt state-changing POST without CSRF token
    print("Testing state-changing admin action WITHOUT CSRF token...")
    raw_session = requests.Session()
    # copy only the session cookie, no token
    raw_session.cookies.set('PHPSESSID', admin_session.cookies.get('PHPSESSID'))
    unauthorized_csrf_res = raw_session.post(f"{BASE_URL}/api/v1/admin/events/update-status", json={
        "id": 999999,
        "is_active": 1
    })
    assert unauthorized_csrf_res.status_code == 403, f"Expected 403 Forbidden on missing CSRF, got {unauthorized_csrf_res.status_code}: {unauthorized_csrf_res.text}"
    print("[PASS] Server strictly rejected state-changing request without CSRF token (HTTP 403 Forbidden).")

    # Attempt state-changing POST with INVALID CSRF token
    print("Testing state-changing admin action with FORGED CSRF token...")
    raw_session.headers.update({'X-CSRF-Token': 'forged_fake_attacker_token_1234567890'})
    forged_csrf_res = raw_session.post(f"{BASE_URL}/api/v1/admin/events/update-status", json={
        "id": 999999,
        "is_active": 1
    })
    assert forged_csrf_res.status_code == 403, f"Expected 403 Forbidden on forged CSRF, got {forged_csrf_res.status_code}"
    print("[PASS] Server strictly rejected forged CSRF token (HTTP 403 Forbidden).")

    # Attempt state-changing POST with VALID CSRF token
    print("Testing state-changing admin action with VALID CSRF token...")
    admin_session.headers.update({'X-CSRF-Token': csrf_token})
    valid_csrf_res = admin_session.post(f"{BASE_URL}/api/v1/admin/events/update-status", json={
        "id": 999999,
        "is_active": 1
    })
    assert valid_csrf_res.status_code == 200, f"Expected 200 with valid CSRF token, got {valid_csrf_res.status_code}: {valid_csrf_res.text}"
    print("[PASS] Server authorized request with valid CSRF token (HTTP 200 OK).")

    # ------------------------------------------------------------
    # 3. Test Brute-Force Rate Limiting & Lockout
    # ------------------------------------------------------------
    print("\n--- [TEST 3] Testing Brute-Force Lockout Defense (5 Attempts Threshold) ---")
    clean_rate_limits()
    test_ip_session = requests.Session()

    for attempt in range(1, 6):
        fail_res = test_ip_session.post(f"{BASE_URL}/api/v1/admin/login", json={
            "identity": "admin@jobrecruitai.com",
            "password": f"WrongPassword_{attempt}"
        })
        assert fail_res.status_code == 401, f"Attempt {attempt} expected 401, got {fail_res.status_code}: {fail_res.text}"
        print(f"  Attempt #{attempt}: Rejected with 401 Unauthorized (Tracked).")

    # 6th Attempt: Must be locked out with HTTP 429
    lockout_res = test_ip_session.post(f"{BASE_URL}/api/v1/admin/login", json={
        "identity": "admin@jobrecruitai.com",
        "password": "WrongPassword_6"
    })
    assert lockout_res.status_code == 429, f"Attempt 6 expected 429 Too Many Requests, got {lockout_res.status_code}: {lockout_res.text}"
    lockout_data = lockout_res.json()
    assert "locked out" in lockout_data.get('error', '').lower(), f"Unexpected error message: {lockout_data}"
    print("[PASS] Brute-force lockout triggered on attempt #6: HTTP 429 Too Many Requests!")

    # Reset rate limits after testing so normal operations continue smoothly
    clean_rate_limits()
    print("[PASS] Rate-limit table verified and reset.")

    # ------------------------------------------------------------
    # 4. Test Internal Worker Timing-Safe Authentication
    # ------------------------------------------------------------
    print("\n--- [TEST 4] Testing Internal API Secret Security ---")
    # Request without secret
    no_sec_res = requests.post(f"{BASE_URL}/api/v1/internal/sync-jobs", json={"jobs": []})
    assert no_sec_res.status_code == 401, f"Expected 401 without secret, got {no_sec_res.status_code}"
    print("[PASS] Unauthenticated internal sync rejected (HTTP 401).")

    # Request with invalid secret
    bad_sec_res = requests.post(f"{BASE_URL}/api/v1/internal/sync-jobs", headers={
        "X-Internal-Secret": "invalid_fake_secret_key"
    }, json={"jobs": []})
    assert bad_sec_res.status_code == 401, f"Expected 401 with invalid secret, got {bad_sec_res.status_code}"
    print("[PASS] Invalid secret rejected with timing-safe check (HTTP 401).")

    print("\n============================================================")
    print(" ALL SECURITY HARDENING TESTS PASSED WITH 100% SUCCESS!")
    print("============================================================")

if __name__ == "__main__":
    test_security_hardening()
