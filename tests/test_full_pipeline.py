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
            'articles', 'fact_claims', 'crawl_runs', 'jobs'
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
            'http://127.0.0.1:8000/api/v1/jobs',
            'http://127.0.0.1:8000/api/v1/exams',
            'http://127.0.0.1:8000/api/v1/articles',
            'http://127.0.0.1:8000/api/v1/admin/metrics'
        ]
        for url in endpoints:
            req = urllib.request.Request(url, headers={'X-Internal-Secret': settings.INTERNAL_API_SECRET})
            with urllib.request.urlopen(req, timeout=5) as response:
                self.assertEqual(response.status, 200, f"Endpoint {url} failed with status {response.status}")
                data = json.loads(response.read().decode())
                self.assertTrue(data.get("success", False))

if __name__ == '__main__':
    unittest.main()
