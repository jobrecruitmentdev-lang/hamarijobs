from typing import List, Dict, Any
from bs4 import BeautifulSoup
from automation.adapters.base import BaseAdapter

class UniversalFallbackAdapter(BaseAdapter):
    """
    Universal Heuristic Fallback Adapter for any Tier 2 / Tier 3 official government portal,
    PSU careers page, or university recruitment noticeboard.
    Uses pattern matching, anchor discovery, and PDF link isolation.
    """
    
    source_name: str = "Universal Fallback"
    domain: str = "gov.in"
    organization: str = "Government Organization"
    source_type: str = "Other"
    priority: str = "Medium"
    requires_js: bool = False
    uses_pdf: bool = True
    
    def __init__(self, domain: str = "", organization: str = "", start_urls: List[str] = None):
        if domain:
            self.domain = domain
        if organization:
            self.organization = organization
        self._start_urls = start_urls or []
        
    def get_target_urls(self) -> List[str]:
        if self._start_urls:
            return self._start_urls
        return [f"https://{self.domain}"]
        
    def extract_job_links(self, html_content: str, base_url: str = "") -> List[str]:
        soup = BeautifulSoup(html_content, "html.parser")
        job_links = []
        
        # Heuristic keywords indicating official job notifications
        keywords = [
            "recruitment", "career", "careers", "vacancy", "vacancies", "job", "jobs",
            "advertisement", "advt", "notice", "circular", "engagement", "employment",
            "bharti", "pariksha", "opening", "walk-in", ".pdf"
        ]
        
        for a_tag in soup.find_all("a", href=True):
            href = a_tag["href"].strip()
            text = a_tag.get_text(strip=True).lower()
            
            if not href or href.startswith("javascript:") or href.startswith("#") or href.startswith("mailto:"):
                continue
                
            is_relevant = any(kw in href.lower() or kw in text for kw in keywords)
            if is_relevant:
                full_url = self.normalize_url(href, base_url)
                if full_url and full_url not in job_links:
                    job_links.append(full_url)
                    
        return job_links

    def parse_job_details(self, html_content: str, url: str) -> Dict[str, Any]:
        soup = BeautifulSoup(html_content, "html.parser")
        title = soup.find("h1") or soup.find("h2") or soup.find("title")
        title_text = title.get_text(strip=True) if title else "Government Recruitment Notice"
        
        return {
            "title": title_text,
            "organization": self.organization or "Government Department",
            "department": "",
            "advertisement_number": "",
            "notification_number": "",
            "vacancies": None,
            "salary_text": "",
            "qualification": "",
            "last_date": None,
            "apply_url": url,
            "notification_pdf_url": url if url.lower().endswith(".pdf") else None,
            "official_source": self.domain,
            "raw_content": soup.get_text(separator=" ", strip=True)[:4000]
        }
