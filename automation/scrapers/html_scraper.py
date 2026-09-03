import time
import random
import requests
import urllib3
from typing import Optional, Dict, Any
from automation.config import settings
from automation.logger import logger

# Suppress insecure HTTPS request warnings if government portal SSL certs are mismatched
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

class HTMLScraper:
    """
    Production-grade static web scraper with rate limiting, retries,
    exponential backoff, polite headers, and SSL fallback for Indian government portals.
    """
    
    USER_AGENTS = [
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:129.0) Gecko/20100101 Firefox/129.0",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"
    ]
    
    def __init__(self, rate_limit_delay: float = None):
        self.rate_limit_delay = rate_limit_delay or settings.RATE_LIMIT_DELAY_SECONDS
        self.session = requests.Session()
        
    def _get_headers(self, custom_headers: Optional[Dict[str, str]] = None) -> Dict[str, str]:
        headers = {
            "User-Agent": random.choice(self.USER_AGENTS),
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language": "en-US,en;q=0.9,hi;q=0.8",
            "Accept-Encoding": "gzip, deflate, br",
            "Connection": "keep-alive",
            "Upgrade-Insecure-Requests": "1"
        }
        if custom_headers:
            headers.update(custom_headers)
        return headers

    def fetch_page(self, url: str, timeout: int = None, retries: int = 3) -> Optional[str]:
        """
        Fetches HTML page content with automatic retries, exponential backoff, and SSL fallback.
        """
        timeout = timeout or settings.CRAWL_TIMEOUT_SECONDS
        headers = self._get_headers()
        
        for attempt in range(1, retries + 1):
            try:
                # Polite crawl delay
                time.sleep(self.rate_limit_delay + random.uniform(0.2, 0.8))
                
                # First attempt with standard verification, fallback to verify=False if SSL cert issue
                try:
                    response = self.session.get(url, headers=headers, timeout=timeout, verify=True)
                except requests.exceptions.SSLError:
                    logger.warning(f"[HTMLScraper] SSL verification failed for {url}. Retrying with verify=False.")
                    response = self.session.get(url, headers=headers, timeout=timeout, verify=False)
                    
                response.raise_for_status()
                
                # Detect encoding
                if response.encoding is None or response.encoding.lower() == "iso-8859-1":
                    response.encoding = response.apparent_encoding or "utf-8"
                    
                return response.text

            except requests.RequestException as e:
                backoff_time = (2 ** attempt) + random.uniform(0.5, 1.5)
                logger.warning(f"[HTMLScraper] Attempt {attempt}/{retries} failed for {url}: {e}. Retrying in {backoff_time:.1f}s...")
                if attempt == retries:
                    logger.error(f"[HTMLScraper] All {retries} attempts failed for {url}: {e}")
                    return None
                time.sleep(backoff_time)
                
        return None
