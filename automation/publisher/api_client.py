import logging
import requests
from typing import Dict, Any, List, Optional
from automation.config import settings
from automation.logger import logger

class PublisherAPI:
    """
    Connects the Python Automation Pipeline to the main Platform Backend API.
    Pushes verified government jobs, recruitment events, and intelligence articles securely.
    """
    
    def __init__(self, base_url: Optional[str] = None, api_secret: Optional[str] = None):
        self.base_url = (base_url or settings.APP_URL).rstrip("/")
        self.api_secret = api_secret or settings.INTERNAL_API_SECRET
        self.headers = {
            "X-Internal-Secret": self.api_secret,
            "Authorization": f"Bearer {self.api_secret}",
            "Content-Type": "application/json",
            "User-Agent": settings.USER_AGENT
        }
        
    def sync_bulk_jobs(self, jobs_list: List[Dict[str, Any]], recruitments_list: Optional[List[Dict[str, Any]]] = None) -> bool:
        """
        Sends scraped government jobs and recruitments to the backend to sync to the live database.
        Splits into reliable chunks to ensure seamless processing across cloud hosting.
        """
        jobs_list = jobs_list or []
        recruitments_list = recruitments_list or []
        if not jobs_list and not recruitments_list:
            logger.warning("[PublisherAPI] No jobs or recruitments provided for sync.")
            return True

        endpoint = f"{self.base_url}{settings.API_V1_STR}/internal/sync-jobs"
        
        # Batch into chunks of 5 for rock-solid reliability across hosting environments
        CHUNK_SIZE = 5
        total_recs = len(recruitments_list)
        total_jobs = len(jobs_list)
        max_len = max(total_recs, total_jobs)
        
        overall_success = True
        synced_recs = 0
        synced_jobs = 0
        
        for i in range(0, max_len, CHUNK_SIZE):
            chunk_recs = recruitments_list[i:i + CHUNK_SIZE]
            chunk_jobs = jobs_list[i:i + CHUNK_SIZE]
            payload = {}
            if chunk_jobs:
                payload["jobs"] = chunk_jobs
            if chunk_recs:
                payload["recruitments"] = chunk_recs
                
            try:
                response = requests.post(endpoint, json=payload, headers=self.headers, timeout=30)
                if response.status_code in (200, 201):
                    data = response.json()
                    synced_recs += data.get("recruitments_synced", len(chunk_recs))
                    synced_jobs += data.get("jobs_synced", len(chunk_jobs))
                    logger.info(f"🌐 [SYNC CHUNK {i//CHUNK_SIZE + 1}] {data.get('message', 'Batch synced successfully')}")
                else:
                    logger.warning(f"⚠️ [SYNC CHUNK FAILED] HTTP {response.status_code}: {response.text}")
                    overall_success = False
            except Exception as e:
                logger.error(f"❌ [SYNC ERROR] Could not reach backend at {endpoint}: {e}")
                overall_success = False
                
        if overall_success:
            logger.info(f"✨ [TOTAL SYNC SUCCESS] Synced {synced_recs} recruitments and {synced_jobs} jobs to {self.base_url}")
        return overall_success

    def sync_recruitment_entity(self, recruitment_data: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        """
        Pushes a verified recruitment entity with its timeline events and related jobs.
        """
        endpoint = f"{self.base_url}{settings.API_V1_STR}/internal/sync-recruitment"
        try:
            response = requests.post(endpoint, json=recruitment_data, headers=self.headers, timeout=20)
            if response.status_code in (200, 201):
                return response.json()
            else:
                logger.warning(f"⚠️ [RECRUITMENT SYNC FAILED] HTTP {response.status_code}: {response.text}")
                return None
        except Exception as e:
            logger.error(f"❌ [RECRUITMENT SYNC ERROR] {e}")
            return None

    def publish_article(self, article_data: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        """
        Publishes an SEO intelligence article.
        """
        endpoint = f"{self.base_url}{settings.API_V1_STR}/internal/sync-article"
        try:
            response = requests.post(endpoint, json=article_data, headers=self.headers, timeout=20)
            if response.status_code in (200, 201):
                return response.json()
            else:
                logger.warning(f"⚠️ [ARTICLE SYNC FAILED] HTTP {response.status_code}: {response.text}")
                return None
        except Exception as e:
            logger.error(f"❌ [ARTICLE SYNC ERROR] {e}")
            return None
