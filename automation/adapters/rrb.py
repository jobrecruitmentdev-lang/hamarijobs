from typing import List, Dict, Any
from bs4 import BeautifulSoup
from urllib.parse import urljoin
from automation.adapters.base import BaseAdapter

class RRBAdapter(BaseAdapter):
    """
    Adapter for Railway Recruitment Control Board (RRB / RRC).
    Official Domain: indianrailways.gov.in / rrbcdg.gov.in / rrbapply.gov.in
    Monitors Centralized Employment Notices (CEN), NTPC, Group D, ALP, JE, and Technician posts.
    """
    
    source_name: str = "RRB"
    domain: str = "rrbapply.gov.in"
    organization: str = "Indian Railways (Railway Recruitment Boards)"
    source_type: str = "Railway"
    priority: str = "Critical"
    requires_js: bool = False
    uses_pdf: bool = True
    
    def get_target_urls(self) -> List[str]:
        return [
            "https://www.rrbapply.gov.in/#/recruitment-notification",
            "https://www.rrbcdg.gov.in/",
            "https://indianrailways.gov.in/railwayboard/view_section.jsp?lang=0&id=0,1,304,366,546"
        ]
        
    def extract_job_links(self, html_content: str, base_url: str = "https://www.rrbapply.gov.in") -> List[str]:
        soup = BeautifulSoup(html_content, "html.parser")
        job_links = []
        
        for a_tag in soup.find_all("a", href=True):
            href = a_tag["href"].strip()
            text = a_tag.get_text(strip=True).lower()
            if ".pdf" in href.lower() or "cen" in href.lower() or "recruitment" in text or "notice" in text:
                full_url = self.normalize_url(href, base_url)
                if full_url not in job_links:
                    job_links.append(full_url)
                    
        return job_links

    def parse_job_details(self, html_content: str, url: str) -> Dict[str, Any]:
        soup = BeautifulSoup(html_content, "html.parser")
        title = soup.find("h1") or soup.find("title")
        title_text = title.get_text(strip=True) if title else "Railway Recruitment Board (RRB) CEN Notice"
        
        return {
            "title": title_text,
            "organization": self.organization,
            "department": "Ministry of Railways",
            "advertisement_number": "",
            "notification_number": "",
            "vacancies": None,
            "salary_text": "Pay Level 2 to Level 7 as per 7th CPC",
            "qualification": "10th Pass / ITI / Diploma / Degree in relevant engineering or general discipline",
            "last_date": None,
            "apply_url": "https://www.rrbapply.gov.in",
            "notification_pdf_url": url if url.lower().endswith(".pdf") else None,
            "official_source": self.domain,
            "raw_content": soup.get_text(separator=" ", strip=True)[:4000]
        }
