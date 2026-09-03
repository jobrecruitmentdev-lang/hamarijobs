from typing import List, Dict, Any
from bs4 import BeautifulSoup
from automation.adapters.base import BaseAdapter

class StatePSCAdapter(BaseAdapter):
    """
    Adapter for State Public Service Commissions:
    - GPSC (Gujarat Public Service Commission - gpsc.gujarat.gov.in)
    - UPPSC (Uttar Pradesh Public Service Commission - uppsc.up.nic.in)
    - BPSC (Bihar Public Service Commission - bpsc.bih.nic.in)
    - MPSC (Maharashtra Public Service Commission - mpsc.gov.in)
    - RPSC (Rajasthan Public Service Commission - rpsc.rajasthan.gov.in)
    - MPPSC (Madhya Pradesh Public Service Commission - mppsc.mp.gov.in)
    """
    
    source_name: str = "State PSC"
    domain: str = "gpsc.gujarat.gov.in"
    organization: str = "State Public Service Commission"
    source_type: str = "StatePSC"
    priority: str = "High"
    requires_js: bool = False
    uses_pdf: bool = True
    
    def get_target_urls(self) -> List[str]:
        return [
            "https://gpsc.gujarat.gov.in/Advertisements",
            "https://uppsc.up.nic.in/",
            "https://bpsc.bih.nic.in/",
            "https://mpsc.gov.in/adv_notifications",
            "https://rpsc.rajasthan.gov.in/news"
        ]
        
    def extract_job_links(self, html_content: str, base_url: str = "") -> List[str]:
        soup = BeautifulSoup(html_content, "html.parser")
        job_links = []
        
        for a_tag in soup.find_all("a", href=True):
            href = a_tag["href"].strip()
            text = a_tag.get_text(strip=True).lower()
            keywords = ["advt", "advertisement", "notification", "recruitment", "pariksha", "bharti", ".pdf", "notice"]
            if any(k in href.lower() or k in text for k in keywords):
                full_url = self.normalize_url(href, base_url)
                if full_url not in job_links:
                    job_links.append(full_url)
                    
        return job_links

    def parse_job_details(self, html_content: str, url: str) -> Dict[str, Any]:
        soup = BeautifulSoup(html_content, "html.parser")
        title = soup.find("h1") or soup.find("h2") or soup.find("title")
        title_text = title.get_text(strip=True) if title else "State PSC Officer Recruitment Notification"
        
        return {
            "title": title_text,
            "organization": self.organization,
            "department": "State Government Administration",
            "advertisement_number": "",
            "notification_number": "",
            "vacancies": None,
            "salary_text": "State Pay Matrix Level 7 to Level 13 (Class 1 & Class 2 Officers)",
            "qualification": "Graduation in relevant discipline / State domicile rules applicable",
            "last_date": None,
            "apply_url": url,
            "notification_pdf_url": url if url.lower().endswith(".pdf") else None,
            "official_source": self.domain,
            "raw_content": soup.get_text(separator=" ", strip=True)[:4000]
        }
