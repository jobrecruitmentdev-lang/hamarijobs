from typing import List, Dict, Any
from bs4 import BeautifulSoup
from urllib.parse import urljoin
from automation.adapters.base import BaseAdapter

class SSCAdapter(BaseAdapter):
    """
    Adapter for Staff Selection Commission (SSC).
    Official Domain: ssc.gov.in
    Monitors Notices, Latest News, Examination Calendars, and Corrigendums.
    """
    
    source_name: str = "SSC"
    domain: str = "ssc.gov.in"
    organization: str = "Staff Selection Commission"
    source_type: str = "SSC"
    priority: str = "Critical"
    requires_js: bool = True
    uses_pdf: bool = True
    
    def get_target_urls(self) -> List[str]:
        return [
            "https://ssc.gov.in/notices",
            "https://ssc.gov.in/candidate-portal/latest-news",
            "https://ssc.gov.in/for-candidates/tentative-vacancy"
        ]
        
    def extract_job_links(self, html_content: str, base_url: str = "https://ssc.gov.in") -> List[str]:
        soup = BeautifulSoup(html_content, "html.parser")
        job_links = []
        
        # SSC portal structure: notice rows / cards / table entries
        for row in soup.find_all(["tr", "div", "li"], class_=lambda c: c and any(k in c.lower() for k in ["notice", "news", "item", "row"])):
            a_tag = row.find("a", href=True)
            if a_tag:
                href = a_tag["href"].strip()
                full_url = self.normalize_url(href, base_url)
                if full_url not in job_links:
                    job_links.append(full_url)
                    
        # Fallback to direct anchor search
        if not job_links:
            for a_tag in soup.find_all("a", href=True):
                href = a_tag["href"].strip()
                if ".pdf" in href.lower() or "notice" in href.lower():
                    full_url = self.normalize_url(href, base_url)
                    if full_url not in job_links:
                        job_links.append(full_url)
                        
        return job_links

    def parse_job_details(self, html_content: str, url: str) -> Dict[str, Any]:
        soup = BeautifulSoup(html_content, "html.parser")
        title = soup.find("h1") or soup.find("h2") or soup.find("title")
        title_text = title.get_text(strip=True) if title else "SSC Examination Notice"
        
        return {
            "title": title_text,
            "organization": self.organization,
            "department": "Department of Personnel and Training (DoPT)",
            "advertisement_number": "",
            "notification_number": "",
            "vacancies": None,
            "salary_text": "Pay Level 2 to Level 8 as per 7th CPC",
            "qualification": "10th / 12th / Graduate based on post",
            "last_date": None,
            "apply_url": "https://ssc.gov.in",
            "notification_pdf_url": url if url.lower().endswith(".pdf") else None,
            "official_source": self.domain,
            "raw_content": soup.get_text(separator=" ", strip=True)[:4000]
        }
