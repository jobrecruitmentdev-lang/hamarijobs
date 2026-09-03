from typing import List, Dict, Any
from bs4 import BeautifulSoup
from urllib.parse import urljoin
from automation.adapters.base import BaseAdapter

class UPSCAdapter(BaseAdapter):
    """
    Adapter for Union Public Service Commission (UPSC).
    Official Domain: upsc.gov.in
    Monitors Active Examinations, Notifications, Recruitment Advertisements, and Results.
    """
    
    source_name: str = "UPSC"
    domain: str = "upsc.gov.in"
    organization: str = "Union Public Service Commission"
    source_type: str = "UPSC"
    priority: str = "Critical"
    requires_js: bool = False
    uses_pdf: bool = True
    supports_rss: bool = True
    
    def get_target_urls(self) -> List[str]:
        return [
            "https://upsc.gov.in/examinations/active-exams",
            "https://upsc.gov.in/recruitment/recruitment-advertisement",
            "https://upsc.gov.in/whats-new"
        ]
        
    def extract_job_links(self, html_content: str, base_url: str = "https://upsc.gov.in") -> List[str]:
        soup = BeautifulSoup(html_content, "html.parser")
        job_links = []
        
        # Look for views-table / content tables on UPSC portal
        for table in soup.find_all("table"):
            for a_tag in table.find_all("a", href=True):
                href = a_tag["href"].strip()
                full_url = self.normalize_url(href, base_url)
                if full_url not in job_links:
                    job_links.append(full_url)
                    
        for a_tag in soup.find_all("a", href=True):
            href = a_tag["href"].strip()
            if any(k in href.lower() for k in [".pdf", "/examination/", "/recruitment/"]):
                full_url = self.normalize_url(href, base_url)
                if full_url not in job_links:
                    job_links.append(full_url)
                    
        return job_links

    def parse_job_details(self, html_content: str, url: str) -> Dict[str, Any]:
        soup = BeautifulSoup(html_content, "html.parser")
        title = soup.find("h1") or soup.find("h2") or soup.find("caption")
        title_text = title.get_text(strip=True) if title else "UPSC Examination Notification"
        
        return {
            "title": title_text,
            "organization": self.organization,
            "department": "Government of India",
            "advertisement_number": "",
            "notification_number": "",
            "vacancies": None,
            "salary_text": "Pay Level 10 (₹56,100 - ₹1,77,500) as per 7th CPC",
            "qualification": "Graduate Degree in any discipline / Relevant Specialization",
            "last_date": None,
            "apply_url": "https://upsconline.nic.in",
            "notification_pdf_url": url if url.lower().endswith(".pdf") else None,
            "official_source": self.domain,
            "raw_content": soup.get_text(separator=" ", strip=True)[:4000]
        }
