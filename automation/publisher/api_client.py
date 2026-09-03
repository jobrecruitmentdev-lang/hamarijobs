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
        
    def sync_bulk_jobs(self, jobs_list: List[Dict[str, Any]]) -> bool:
        """
        Sends the batch of scraped government jobs to the backend to sync to the live database.
        """
        if not jobs_list:
            logger.warning("[PublisherAPI] No jobs provided for sync.")
            return True

        endpoint = f"{self.base_url}{settings.API_V1_STR}/internal/sync-jobs"
        try:
            response = requests.post(endpoint, json={"jobs": jobs_list}, headers=self.headers, timeout=20)
            if response.status_code in (200, 201):
                data = response.json()
                logger.info(f"🌐 [SYNC SUCCESS] {data.get('message', 'Jobs synced successfully')}")
                return True
            else:
                logger.warning(f"⚠️ [SYNC FAILED] HTTP {response.status_code}: {response.text}")
                return False
        except Exception as e:
            logger.error(f"❌ [SYNC ERROR] Could not reach backend at {endpoint}: {e}")
            return False

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
