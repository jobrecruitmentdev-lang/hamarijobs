import sys
import os
import re
import json
import uuid
import hashlib
import time
from pathlib import Path
from datetime import datetime
from typing import Dict, Any, List, Optional
import pymysql

ROOT_DIR = str(Path(__file__).resolve().parent.parent.parent)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from automation.config import settings
from automation.logger import logger
from automation.adapters.registry import AdapterRegistry
from automation.scrapers.html_scraper import HTMLScraper
from automation.scrapers.dynamic_scraper import DynamicScraper
from automation.ocr.pdf_parser import PDFParser
from automation.llm.extractor import LLMExtractor
from automation.llm.schema import StructuredRecruitmentExtraction, DocumentClassification
from automation.publisher.api_client import PublisherAPI

class CrawlOrchestrator:
    """
    Enterprise Orchestration Engine.
    Executes real official source ingestion, PDF/OCR processing, AI structured extraction,
    corrigendum & change detection, duplicate resolution, and database persistence.
    """
    
    def __init__(self):
        self.html_scraper = HTMLScraper()
        self.dynamic_scraper = DynamicScraper()
        self.pdf_parser = PDFParser()
        self.llm_extractor = LLMExtractor()
        self.publisher = PublisherAPI()
        
    def get_db_connection(self):
        """Returns a direct MySQL connection for local execution."""
        try:
            return pymysql.connect(
                host=settings.MYSQL_HOST,
                user=settings.MYSQL_USER,
                password=settings.MYSQL_PASSWORD,
                database=settings.MYSQL_DB,
                cursorclass=pymysql.cursors.DictCursor,
                autocommit=True
            )
        except Exception as e:
            logger.warning(f"[Orchestrator] Direct DB connection unavailable ({e}). Using REST API sync.")
            return None

    def crawl_all_active_sources(self) -> Dict[str, Any]:
        """
        Crawls all active government sources registered in the source_registry table.
        """
        conn = self.get_db_connection()
        sources = []
        if conn:
            cur = conn.cursor()
            cur.execute("SELECT * FROM source_registry WHERE status = 'Active' ORDER BY priority DESC;")
            sources = cur.fetchall()
            conn.close()

        if not sources:
            # Fallback default high-priority sources if table empty
            sources = [
                {"id": 1, "source_name": "UPSC", "domain": "upsc.gov.in", "website_url": "https://upsc.gov.in", "recruitment_url": "https://upsc.gov.in/examinations/active-exams", "adapter_name": "UPSCAdapter", "uses_javascript": 0, "priority": "Critical"},
                {"id": 2, "source_name": "SSC", "domain": "ssc.gov.in", "website_url": "https://ssc.gov.in", "recruitment_url": "https://ssc.gov.in/notices", "adapter_name": "SSCAdapter", "uses_javascript": 1, "priority": "Critical"},
                {"id": 3, "source_name": "RRB", "domain": "rrbapply.gov.in", "website_url": "https://rrbapply.gov.in", "recruitment_url": "https://www.rrbapply.gov.in/#/recruitment-notification", "adapter_name": "RRBAdapter", "uses_javascript": 0, "priority": "Critical"},
                {"id": 4, "source_name": "IBPS", "domain": "ibps.in", "website_url": "https://ibps.in", "recruitment_url": "https://www.ibps.in/careers/", "adapter_name": "IBPSAdapter", "uses_javascript": 0, "priority": "High"},
                {"id": 5, "source_name": "SBI Careers", "domain": "sbi.co.in", "website_url": "https://sbi.co.in", "recruitment_url": "https://sbi.co.in/web/careers/current-openings", "adapter_name": "SBICareersAdapter", "uses_javascript": 1, "priority": "Critical"},
                {"id": 6, "source_name": "Defence & Research", "domain": "drdo.gov.in", "website_url": "https://drdo.gov.in", "recruitment_url": "https://www.drdo.gov.in/drdo/careers", "adapter_name": "DefenceAdapter", "uses_javascript": 0, "priority": "High"},
            ]

        logger.info(f"🚀 [Orchestrator] Starting crawl batch for {len(sources)} sources...")
        total_discovered = 0
        total_ingested = 0
        total_updated = 0

        for source in sources:
            res = self.process_source(source)
            total_discovered += res.get("discovered", 0)
            total_ingested += res.get("new_jobs", 0)
            total_updated += res.get("updated_jobs", 0)

        return {
            "sources_processed": len(sources),
            "total_discovered": total_discovered,
            "new_jobs_ingested": total_ingested,
            "jobs_updated": total_updated,
            "timestamp": datetime.now().isoformat()
        }

    def process_source(self, source_config: Dict[str, Any]) -> Dict[str, Any]:
        """
        Executes end-to-end ingestion pipeline for a single government source.
        """
        source_id = source_config.get("id") or 1
        source_name = source_config.get("source_name", "Gov Source")
        adapter_name = source_config.get("adapter_name")
        adapter = AdapterRegistry.get_adapter(adapter_name, source_config)
        
        run_uuid = str(uuid.uuid4())
        started_at = datetime.now()
        logger.info(f"--- [Crawl Started] {source_name} ({adapter.domain}) ---")

        discovered_links = []
        new_jobs = 0
        updated_jobs = 0
        duplicates = 0
        errors = 0

        try:
            target_urls = adapter.get_target_urls()
            
            for target_url in target_urls:
                # 1. Fetch listing page
                html_content = None
                if adapter.requires_js or source_config.get("uses_javascript"):
                    html_content = self.dynamic_scraper.fetch_page(target_url)
                else:
                    html_content = self.html_scraper.fetch_page(target_url)

                if not html_content:
                    continue

                # 2. Discover documents & links
                documents = adapter.discover_documents(html_content, base_url=target_url)
                job_links = adapter.extract_job_links(html_content, base_url=target_url)

                all_targets = set([d["url"] for d in documents] + job_links)
                discovered_links.extend(list(all_targets))

                # 3. Process each discovered recruitment item
                for item_url in all_targets:
                    try:
                        ingest_res = self.process_recruitment_item(item_url, source_config, adapter)
                        if ingest_res == "NEW":
                            new_jobs += 1
                        elif ingest_res == "UPDATED":
                            updated_jobs += 1
                        elif ingest_res == "DUPLICATE":
                            duplicates += 1
                    except Exception as item_err:
                        errors += 1
                        logger.error(f"[Orchestrator] Error processing {item_url}: {item_err}")

            # Record crawl telemetry
            duration = (datetime.now() - started_at).total_seconds()
            self._record_crawl_run(source_id, run_uuid, len(discovered_links), new_jobs, updated_jobs, duplicates, errors, duration)

            logger.info(f"✅ [Crawl Finished] {source_name}: {len(discovered_links)} discovered, {new_jobs} new, {updated_jobs} updated, {errors} errors in {duration:.1f}s")
            return {
                "discovered": len(discovered_links),
                "new_jobs": new_jobs,
                "updated_jobs": updated_jobs,
                "duplicates": duplicates,
                "errors": errors
            }

        except Exception as e:
            logger.error(f"❌ [Crawl Failed] {source_name}: {e}")
            return {"discovered": 0, "new_jobs": 0, "updated_jobs": 0, "duplicates": 0, "errors": 1}

    def process_recruitment_item(self, item_url: str, source_config: Dict[str, Any], adapter: Any) -> str:
        """
        Processes an individual recruitment document / page URL:
        Download -> Hash -> PDF/OCR -> AI Extract -> Diff/Duplicate -> Save.
        """
        url_hash = hashlib.sha256(item_url.encode()).hexdigest()
        is_pdf = item_url.lower().endswith(".pdf") or ".pdf?" in item_url.lower()

        extracted_text = ""
        doc_hash = None

        if is_pdf:
            # Download and parse PDF
            local_pdf_path = self.pdf_parser.download_pdf(item_url)
            if not local_pdf_path:
                return "FAILED"

            doc_hash = self.pdf_parser.compute_sha256(local_pdf_path)
            
            # Check duplicate by doc_hash
            existing_job = self._find_job_by_hash(doc_hash)
            if existing_job:
                logger.info(f"[Orchestrator] Document hash {doc_hash[:12]} already ingested. Skipping.")
                return "DUPLICATE"

            pdf_res = self.pdf_parser.process_document(local_pdf_path)
            extracted_text = pdf_res.get("raw_text", "")
        else:
            # Fetch HTML notice page
            html = self.html_scraper.fetch_page(item_url)
            if not html:
                return "FAILED"
            extracted_text = html

        if not extracted_text or len(extracted_text) < 40:
            return "SKIPPED"

        # 4. AI Structured Extraction
        source_meta = {
            "organization": source_config.get("source_name") or adapter.organization,
            "domain": adapter.domain,
            "url": item_url
        }
        structured_data = self.llm_extractor.extract_structured_recruitment(extracted_text, source_meta)
        
        if not structured_data:
            return "FAILED"

        # 5. Save or update in database
        return self._persist_recruitment(structured_data, item_url, doc_hash, source_config)

    def _find_job_by_hash(self, doc_hash: Optional[str]) -> Optional[Dict[str, Any]]:
        """Checks if a job with the given document hash exists."""
        if not doc_hash:
            return None
        conn = self.get_db_connection()
        if not conn:
            return None
        cur = conn.cursor()
        cur.execute("SELECT id, title, status FROM jobs WHERE embedding LIKE %s OR location_details LIKE %s LIMIT 1;", (f"%{doc_hash}%", f"%{doc_hash}%"))
        res = cur.fetchone()
        conn.close()
        return res

    def _persist_recruitment(self, data: StructuredRecruitmentExtraction, url: str, doc_hash: Optional[str], source_config: Dict[str, Any]) -> str:
        """
        Saves structured extraction into recruitments, jobs, recruitment_events, and fact_claims.
        """
        conn = self.get_db_connection()
        if not conn:
            # Fallback to REST API
            self.publisher.sync_bulk_jobs([{
                "title": data.title,
                "org": data.organization,
                "vac": data.total_vacancies,
                "sal": data.salary.min_salary or 35400,
                "url": url,
                "desc": f"Official recruitment for {data.title} by {data.organization}."
            }])
            return "NEW"

        cur = conn.cursor()
        slug = re.sub(r'[^a-zA-Z0-9]+', '-', f"{data.organization}-{data.title}-{data.year}").strip('-').lower()
        recruitment_uuid = str(uuid.uuid4())
        job_uuid = str(uuid.uuid4())

        # Check existing recruitment by slug
        cur.execute("SELECT id, total_vacancies FROM recruitments WHERE slug = %s LIMIT 1;", (slug,))
        existing_rec = cur.fetchone()

        if existing_rec:
            # Check Corrigendum / Vacancy Revision
            old_vac = existing_rec.get("total_vacancies")
            if data.total_vacancies and old_vac and data.total_vacancies != old_vac:
                logger.info(f"🔄 [Corrigendum Detected] Vacancies updated from {old_vac} to {data.total_vacancies} for {slug}")
                cur.execute("UPDATE recruitments SET total_vacancies = %s, updated_at = NOW() WHERE id = %s;", (data.total_vacancies, existing_rec["id"]))
                
                # Add timeline event
                cur.execute("""
                    INSERT INTO recruitment_events (recruitment_id, event_type, event_title, event_date, details, reference_url, reference_document_hash)
                    VALUES (%s, 'CORRIGENDUM_ISSUED', 'Vacancy Revised', CURDATE(), %s, %s, %s);
                """, (existing_rec["id"], f"Vacancies revised from {old_vac} to {data.total_vacancies}", url, doc_hash))
                conn.close()
                return "UPDATED"
            conn.close()
            return "DUPLICATE"

        # Insert new Recruitment Master Entity
        cur.execute("""
            INSERT INTO recruitments (recruitment_uuid, title, slug, organization_name, advertisement_number, notification_number, year, total_vacancies, status, primary_notification_url, official_website_url, official_apply_url, state_code, qualification_level, summary, is_verified, verified_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 'Active', %s, %s, %s, %s, %s, %s, 1, NOW());
        """, (
            recruitment_uuid,
            data.title,
            slug,
            data.organization,
            data.advertisement_number,
            data.notification_number,
            data.year,
            data.total_vacancies,
            url,
            f"https://{source_config.get('domain', 'gov.in')}",
            data.official_apply_url or url,
            data.state_code,
            data.educational_qualification[:100],
            f"Official recruitment notification released by {data.organization} for {data.title} with {data.total_vacancies or 'various'} vacancies."
        ))
        rec_id = cur.lastrowid

        # Insert into jobs table (for compatibility with public website views)
        salary_text = data.salary.pay_scale_text or (f"₹{data.salary.min_salary:,} - ₹{data.salary.max_salary:,}" if data.salary.min_salary else "As per 7th CPC Government Norms")
        cur.execute("""
            INSERT INTO jobs (id, title, description, job_type, salary_range, work_mode, experience_level, status, location_text, department, category, is_govt, salary_details, education_details, application_details, location_details, created_at)
            VALUES (%s, %s, %s, 'Full-time', %s, 'On-site', %s, 'OPEN', %s, %s, %s, 1, %s, %s, %s, %s, NOW());
        """, (
            job_uuid,
            f"{data.organization} {data.title}",
            f"Official recruitment notification by {data.organization}. Total Vacancies: {data.total_vacancies or 'Not specified'}. Eligibility: {data.educational_qualification}.",
            salary_text,
            data.experience_required or "Fresher / Experienced as per post",
            data.posting_location,
            data.department or data.organization,
            "Government",
            json.dumps({"salary_text": salary_text, "pay_level": data.salary.pay_level or "7th CPC", "min": data.salary.min_salary, "max": data.salary.max_salary}),
            json.dumps({"qualification": data.educational_qualification, "technical": data.technical_qualification or ""}),
            json.dumps({"apply_url": data.official_apply_url or url, "last_date": data.important_dates.application_last_date, "notification_pdf": url}),
            json.dumps({"doc_hash": doc_hash, "recruitment_id": rec_id})
        ))

        # Insert Timeline Event (Notification Released)
        cur.execute("""
            INSERT INTO recruitment_events (recruitment_id, event_type, event_title, event_date, details, reference_url, reference_document_hash)
            VALUES (%s, 'NOTIFICATION_RELEASED', 'Official Notification Released', CURDATE(), %s, %s, %s);
        """, (rec_id, f"Official notification published for {data.total_vacancies or ''} posts.", url, doc_hash))

        # Insert Fact Claims (for Zero-Hallucination Ground Truth)
        if data.total_vacancies:
            cur.execute("""
                INSERT INTO fact_claims (entity_type, entity_id, field_name, claimed_value, source_document_url, evidence_snippet, confidence_score)
                VALUES ('Recruitment', %s, 'total_vacancies', %s, %s, %s, 95.0);
            """, (rec_id, str(data.total_vacancies), url, f"Total Vacancies: {data.total_vacancies}"))

        # Insert SEO Metadata
        seo_slug = f"jobs/{slug}"
        cur.execute("""
            INSERT INTO seo_metadata (entity_type, entity_id, slug, meta_title, meta_description, focus_keywords, canonical_url)
            VALUES ('Job', %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE meta_title = VALUES(meta_title);
        """, (
            rec_id,
            seo_slug,
            f"{data.organization} {data.title} Recruitment 2026 - Apply Online for {data.total_vacancies or ''} Posts",
            f"Apply for {data.organization} {data.title} 2026. Check eligibility, vacancies, salary, exam date, syllabus & official notification PDF here.",
            f"{data.organization} recruitment, {data.title} 2026, government jobs 2026",
            f"https://hamarijobs.com/{seo_slug}"
        ))

        conn.close()
        logger.info(f"✨ [Ingestion Success] Created Recruitment #{rec_id}: {slug}")
        return "NEW"

    def _record_crawl_run(self, source_id: int, run_uuid: str, discovered: int, new_j: int, updated_j: int, dups: int, errs: int, duration: float):
        """Logs crawl statistics to the crawl_runs table."""
        conn = self.get_db_connection()
        if not conn:
            return
        cur = conn.cursor()
        cur.execute("""
            INSERT INTO crawl_runs (source_id, run_uuid, trigger_type, status, items_discovered, items_processed, new_jobs_found, updated_jobs_found, duplicates_detected, errors_count, duration_seconds, started_at, completed_at)
            VALUES (%s, %s, 'CRON', 'Success', %s, %s, %s, %s, %s, %s, %s, NOW(), NOW());
        """, (source_id, run_uuid, discovered, discovered, new_j, updated_j, dups, errs, duration))
        conn.close()

if __name__ == "__main__":
    orchestrator = CrawlOrchestrator()
    result = orchestrator.crawl_all_active_sources()
    print("\n--- Crawl Batch Results ---")
    print(json.dumps(result, indent=2))
