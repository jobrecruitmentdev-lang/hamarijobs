import unittest
import os
import sys
import json
import urllib.request
from pathlib import Path

ROOT_DIR = str(Path(__file__).resolve().parent.parent)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from automation.config import settings
from automation.adapters.registry import AdapterRegistry
from automation.ocr.pdf_parser import PDFParser
from automation.llm.extractor import LLMExtractor
from automation.intelligence.exam_engine import ExamIntelligenceEngine
from automation.intelligence.content_engine import ContentIntelligenceEngine
from automation.seo.sitemap_generator import SitemapAndSEOEngine
import pymysql

class TestGovernmentRecruitmentIntelligence(unittest.TestCase):

    def setUp(self):
        self.conn = pymysql.connect(
            host=settings.MYSQL_HOST,
            port=settings.MYSQL_PORT,
            user=settings.MYSQL_USER,
            password=settings.MYSQL_PASSWORD,
            database=settings.MYSQL_DB,
            cursorclass=pymysql.cursors.DictCursor
        )

    def tearDown(self):
        self.conn.close()

    def test_01_database_tables_exist(self):
        """Verify all critical relational schema tables exist in MySQL"""
        cur = self.conn.cursor()
        cur.execute("SHOW TABLES;")
        tables = [list(r.values())[0] for r in cur.fetchall()]
        
        required_tables = [
            'source_registry', 'recruitments', 'recruitment_events', 'exams',
            'exam_phases', 'exam_patterns', 'exam_syllabus', 'cutoff_records',
            'articles', 'fact_claims', 'crawl_runs', 'jobs',
            'automation_runs', 'notice_hash_cache'
        ]
        for tbl in required_tables:
            self.assertIn(tbl, tables, f"Database table '{tbl}' is missing!")

    def test_02_adapter_registry(self):
        """Verify dynamic adapter discovery and routing"""
        registry = AdapterRegistry()
        adapters = registry.get_all_adapters()
        self.assertGreaterEqual(len(adapters), 8, "Expected at least 8 specialized adapters")

        # Test specific domain matching
        ssc = registry.get_adapter_by_domain("ssc.gov.in")
        self.assertIsNotNone(ssc)
        self.assertIn("SSC", ssc.source_name)

        upsc = registry.get_adapter_by_domain("upsc.gov.in")
        self.assertIsNotNone(upsc)
        self.assertIn("UPSC", upsc.source_name)

    def test_03_structured_ai_extraction_and_facts(self):
        """Verify zero-hallucination structured extraction with evidence tracking"""
        extractor = LLMExtractor()
        sample_notice = """
        STAFF SELECTION COMMISSION (SSC)
        NOTICE: COMBINED GRADUATE LEVEL EXAMINATION, 2026
        Tentative Vacancies: There are approx. 7,500 vacancies.
        Pay Scale: Pay Level-7 (Rs. 44,900 to 1,42,400).
        Essential Qualification: Bachelor's Degree from a recognized University.
        Age Limit: 18-30 years as on 01-08-2026.
        """
        extracted = extractor.extract_structured_recruitment(sample_notice, {"organization": "SSC", "title": "CGL 2026"})
        self.assertIn("SSC", extracted.organization)
        self.assertEqual(extracted.total_vacancies, 7500)
        self.assertTrue("Graduate" in extracted.educational_qualification or "Bachelor" in extracted.educational_qualification)
        self.assertGreaterEqual(extracted.confidence_score, 85)

    def test_04_exam_intelligence_hub(self):
        """Verify Exam Intelligence Hub data retrieval"""
        engine = ExamIntelligenceEngine()
        exam = engine.get_exam_hub_by_slug("ssc-cgl")
        self.assertIsNotNone(exam, "SSC CGL Exam Hub must exist in database")
        self.assertIn("phases", exam)
        self.assertIn("patterns", exam)
        self.assertIn("syllabus", exam)
        self.assertIn("cutoffs", exam)
        self.assertGreater(len(exam["syllabus"]), 0, "Syllabus topics should be present")

    def test_05_content_intelligence_quality(self):
        """Verify fact-anchored article generation score >= 85"""
        cur = self.conn.cursor()
        cur.execute("SELECT id FROM recruitments LIMIT 1;")
        rec = cur.fetchone()
        if rec:
            engine = ContentIntelligenceEngine()
            articles = engine.generate_recruitment_pillar_articles(rec["id"])
            for art in articles:
                self.assertGreaterEqual(art["quality_score"], 85, "Generated article must pass strict quality benchmark")

    def test_06_dynamic_xml_sitemaps(self):
        """Verify valid XML sitemaps generation"""
        engine = SitemapAndSEOEngine()
        sitemaps = engine.generate_all_sitemaps()
        self.assertTrue(os.path.exists(sitemaps["index"]))
        self.assertTrue(os.path.exists(sitemaps["jobs"]))
        self.assertTrue(os.path.exists(sitemaps["exams"]))
        self.assertTrue(os.path.exists(sitemaps["articles"]))

    def test_07_http_api_endpoints(self):
        """Verify PHP REST API returns HTTP 200 with valid JSON"""
        endpoints = [
            'http://127.0.0.1:8080/api/v1/jobs',
            'http://127.0.0.1:8080/api/v1/exams',
            'http://127.0.0.1:8080/api/v1/articles',
            'http://127.0.0.1:8080/api/v1/admin/metrics'
        ]
        for url in endpoints:
            req = urllib.request.Request(url, headers={'X-Internal-Secret': settings.INTERNAL_API_SECRET})
            with urllib.request.urlopen(req, timeout=5) as response:
                self.assertEqual(response.status, 200, f"Endpoint {url} failed with status {response.status}")
                data = json.loads(response.read().decode())
                self.assertTrue(data.get("success", False))

    def test_08_fact_verification_shield(self):
        """Verify Fact-Verification Double Shield detects domain mismatches & vacancy anomalies"""
        from automation.intelligence.verification import FactVerificationShield
        from automation.llm.schema import StructuredRecruitmentExtraction, SalaryDetails, AgeLimit

        # Anomaly case: Non-government domain and excessive vacancies
        fake_rec = StructuredRecruitmentExtraction(
            organization="Private Corp",
            title="Fake Job Notice 2026",
            total_vacancies=999999,
            salary=SalaryDetails(pay_scale_text="Level 7"),
            age_limit=AgeLimit(min_age=18, max_age=30),
            educational_qualification="Graduate",
            official_apply_url="https://fake-freejob-portal.com",
            confidence_score=90
        )
        status, flags = FactVerificationShield.verify_recruitment_data(fake_rec, {"domain": "fake-freejob-portal.com"})
        self.assertEqual(status, "REVIEW_PENDING")
        self.assertIn("ANOMALY_UNVERIFIED_APPLY_DOMAIN", flags)
        self.assertIn("ANOMALY_UNREALISTIC_VACANCY_COUNT", flags)

    def test_09_hash_change_detector(self):
        """Verify Cryptographic SHA-256 Hash Change Detector skips duplicate content"""
        import uuid
        from automation.scrapers.hash_detector import NoticeHashDetector
        detector = NoticeHashDetector()
        
        test_domain = "upsc.gov.in"
        test_url = f"https://upsc.gov.in/test_notice_{uuid.uuid4().hex[:8]}.pdf"
        test_content = "Official Notification Text for CDS 2026 Examination"
        h = detector.calculate_sha256(test_content)
        
        # First time: must detect as changed / new
        changed = detector.has_content_changed(test_domain, test_url, h)
        self.assertTrue(changed)
        
        # Record it
        detector.record_notice_hash(test_domain, test_url, h, "CDS 2026")
        
        # Second time: identical hash must return changed = False
        changed_second = detector.has_content_changed(test_domain, test_url, h)
        self.assertFalse(changed_second)

    def test_10_daemon_status_and_runs(self):
        """Verify Daemon Status and Automation Runs APIs respond properly"""
        urls = [
            'http://127.0.0.1:8080/api/v1/admin/daemon/status',
            'http://127.0.0.1:8080/api/v1/admin/automation-runs',
            'http://127.0.0.1:8080/api/v1/admin/review-queue'
        ]
        for u in urls:
            req = urllib.request.Request(u, headers={'X-Internal-Secret': settings.INTERNAL_API_SECRET})
            with urllib.request.urlopen(req, timeout=5) as resp:
                self.assertEqual(resp.status, 200)
                d = json.loads(resp.read().decode())
                self.assertTrue(d.get("success", False))

if __name__ == '__main__':
    unittest.main()
