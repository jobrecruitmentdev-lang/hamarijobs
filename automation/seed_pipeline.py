import sys
from pathlib import Path
ROOT_DIR = str(Path(__file__).resolve().parent.parent)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from automation.logger import logger
from automation.engine.orchestrator import CrawlOrchestrator
from automation.intelligence.exam_engine import ExamIntelligenceEngine
from automation.intelligence.content_engine import ContentIntelligenceEngine
from automation.seo.sitemap_generator import SitemapAndSEOEngine
import pymysql
from automation.config import settings

def run_seed_pipeline():
    logger.info("==================================================")
    logger.info("🚀 STARTING FULL GOVERNMENT RECRUITMENT PIPELINE")
    logger.info("==================================================")

    # 1. Seed Exam Hubs
    exam_engine = ExamIntelligenceEngine()
    exam_engine.seed_master_exam_hubs()

    # 2. Ingest Verified Official Government Notifications
    orchestrator = CrawlOrchestrator()
    
    sample_gazettes = [
        {
            "meta": {"organization": "SSC", "domain": "ssc.gov.in", "title": "Combined Graduate Level (CGL) Examination 2026"},
            "text": """
STAFF SELECTION COMMISSION (SSC)
NOTICE: COMBINED GRADUATE LEVEL EXAMINATION, 2026
Dates for submission of online applications: 24-06-2026 to 24-07-2026.
Last date and time for receipt of online applications: 24-07-2026 (23:00).
Tentative Vacancies: There are approx. 7,500 vacancies to be filled through this examination.
Pay Scale: Pay Level-7 (Rs. 44,900 to 1,42,400) and Level-4 to Level-8 under 7th CPC.
Essential Educational Qualifications: Bachelor's Degree from a recognized University or equivalent.
Age Limit: 18-30 years / 18-32 years as on 01-08-2026 with statutory relaxations.
Scheme of Examination: Computer Based Examination (Tier-I and Tier-II).
            """
        },
        {
            "meta": {"organization": "UPSC", "domain": "upsc.gov.in", "title": "Civil Services Examination 2026"},
            "text": """
UNION PUBLIC SERVICE COMMISSION (UPSC)
EXAMINATION NOTICE NO. 05/2026-CSP
CIVIL SERVICES (PRELIMINARY) EXAMINATION, 2026
Last Date for Receipt of Applications: 05-03-2026.
Total Vacancies: Expected to be approximately 1056 vacancies across IAS, IFS, IPS, IRS cadres.
Educational Qualification: Graduate Degree of any recognized University.
Age Limit: 21 to 32 years on 1st of August, 2026.
Pay Scale: Pay Level 10 (Rs. 56,100 - 1,77,500) as per 7th CPC.
Scheme of Exam: Civil Services Preliminary Exam followed by Civil Services Main Exam and Personality Test.
            """
        },
        {
            "meta": {"organization": "RRB", "domain": "rrbapply.gov.in", "title": "Non-Technical Popular Categories (NTPC) CEN 05/2026"},
            "text": """
GOVERNMENT OF INDIA, MINISTRY OF RAILWAYS
RAILWAY RECRUITMENT BOARDS (RRB)
CENTRALIZED EMPLOYMENT NOTICE (CEN) No. 05/2026 - NTPC
Opening date of online registration: 14-09-2026. Closing date: 13-10-2026.
Total Vacancies: 11,558 vacancies across Indian Railway Zones.
Posts: Station Master, Goods Guard, Senior Clerk cum Typist, Junior Clerk, Accounts Clerk.
Pay Scale: Level 2 to Level 6 (Rs. 19,900 - Rs. 35,400 per month).
Educational Qualification: 12th Pass / Graduate Degree in any discipline.
Age Criteria: 18 to 36 years.
            """
        },
        {
            "meta": {"organization": "IBPS", "domain": "ibps.in", "title": "Common Recruitment Process for PO/MT (CRP PO/MT-XVI)"},
            "text": """
INSTITUTE OF BANKING PERSONNEL SELECTION (IBPS)
NOTIFICATION: RECRUITMENT OF PROBATIONARY OFFICERS / MANAGEMENT TRAINEES (CRP PO/MT-XVI)
Online registration including Edit/Modification: 01-08-2026 to 21-08-2026.
Tentative Vacancies: 3,955 vacancies in participating Public Sector Banks.
Educational Qualification: A Degree (Graduation) in any discipline from a University recognized by the Govt. of India.
Age Limit: 20 to 30 years as on 01-08-2026.
Pay Scale: Basic Pay of Rs. 36,000/- plus DA, HRA, CCA allowances.
Selection Scheme: Online Preliminary Examination, Online Main Examination followed by Interview.
            """
        },
        {
            "meta": {"organization": "Indian Air Force", "domain": "afcat.cdac.in", "title": "Air Force Common Admission Test (AFCAT 02/2026)"},
            "text": """
INDIAN AIR FORCE
DIRECTORATE OF RECRUITMENT
AIR FORCE COMMON ADMISSION TEST (AFCAT - 02/2026) / NCC SPECIAL ENTRY
Online Applications: 01 Jun 2026 to 30 Jun 2026.
Vacancies: 317 Commissioned Officer posts in Flying Branch & Ground Duty (Technical and Non-Technical).
Pay Scale: Flying Officer Pay Level 10 (Rs. 56,100 - 1,77,500) with Military Service Pay (MSP) of Rs. 15,500 pm.
Educational Qualification: 10+2 with minimum 50% in Maths & Physics and Graduation (minimum 60%) or B.E./B.Tech degree.
Age Limit: 20 to 24 years for Flying Branch, 20 to 26 years for Ground Duty.
            """
        },
        {
            "meta": {"organization": "GPSC", "domain": "gpsc.gujarat.gov.in", "title": "Gujarat Administrative Service Class-1 & Class-2 Examination"},
            "text": """
GUJARAT PUBLIC SERVICE COMMISSION (GPSC)
ADVERTISEMENT NO. GPSC/202627/01
GUJARAT ADMINISTRATIVE SERVICE (GAS) CLASS-1 & GUJARAT CIVIL SERVICE CLASS-1 & 2
Last Date to Apply Online: 15-09-2026.
Total Vacancies: 260 Class-1 & Class-2 Officer posts in Government of Gujarat.
Pay Scale: State Pay Matrix Level 8 to Level 13 (Rs. 44,900 - 1,42,400).
Educational Qualification: Graduate in any discipline from a recognized University.
Age Limit: 20 to 36 years with Gujarat state reservation benefits.
            """
        }
    ]

    ingested_rec_ids = []
    conn = orchestrator.get_db_connection()
    cur = conn.cursor()

    for item in sample_gazettes:
        meta = item["meta"]
        text = item["text"]
        
        extracted = orchestrator.llm_extractor.extract_structured_recruitment(text, meta)
        source_cfg = {"domain": meta["domain"], "source_name": meta["organization"]}
        doc_hash = f"sha256_mock_seed_{meta['organization']}"
        
        res = orchestrator._persist_recruitment(extracted, f"https://{meta['domain']}/notification.pdf", doc_hash, source_cfg)
        logger.info(f"Ingested {meta['organization']} {meta['title']}: {res}")

    cur.execute("SELECT id FROM recruitments;")
    rec_rows = cur.fetchall()
    ingested_rec_ids = [r["id"] for r in rec_rows]
    conn.close()

    # 3. Generate Fact-Anchored SEO Pillar Articles
    content_engine = ContentIntelligenceEngine()
    total_articles = 0
    for rec_id in ingested_rec_ids:
        arts = content_engine.generate_recruitment_pillar_articles(rec_id)
        total_articles += len(arts)

    # 4. Generate Dynamic XML Sitemaps
    seo_engine = SitemapAndSEOEngine()
    sitemaps = seo_engine.generate_all_sitemaps()

    logger.info("==================================================")
    logger.info(f"✅ PIPELINE COMPLETE: {len(ingested_rec_ids)} Recruitments, {total_articles} Articles, {len(sitemaps)} Sitemaps")
    logger.info("==================================================")

if __name__ == "__main__":
    run_seed_pipeline()
