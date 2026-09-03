from typing import List, Dict, Any
from bs4 import BeautifulSoup
from automation.adapters.base import BaseAdapter

class SBICareersAdapter(BaseAdapter):
    """
    Adapter for State Bank of India (SBI).
    Official Domain: sbi.co.in/web/careers
    Monitors Probationary Officers (PO), Junior Associates (Clerical), Circle Based Officers (CBO), and Specialist Officers (SCO).
    """
    
    source_name: str = "SBI Careers"
    domain: str = "sbi.co.in"
    organization: str = "State Bank of India"
    source_type: str = "Bank"
    priority: str = "Critical"
    requires_js: bool = True
    uses_pdf: bool = True
    
    def get_target_urls(self) -> List[str]:
        return [
            "https://sbi.co.in/web/careers/current-openings",
            "https://bank.sbi/web/careers/current-openings"
        ]
        
    def extract_job_links(self, html_content: str, base_url: str = "https://sbi.co.in") -> List[str]:
        soup = BeautifulSoup(html_content, "html.parser")
        job_links = []
        
        for a_tag in soup.find_all("a", href=True):
            href = a_tag["href"].strip()
            text = a_tag.get_text(strip=True).lower()
            if ".pdf" in href.lower() or "crpd" in href.lower() or "recruitment" in text or "advertisement" in text:
                full_url = self.normalize_url(href, base_url)
                if full_url not in job_links:
                    job_links.append(full_url)
                    
        return job_links

    def parse_job_details(self, html_content: str, url: str) -> Dict[str, Any]:
        soup = BeautifulSoup(html_content, "html.parser")
        title = soup.find("h1") or soup.find("h2") or soup.find("title")
        title_text = title.get_text(strip=True) if title else "SBI Recruitment Opening"
        
        return {
            "title": title_text,
            "organization": self.organization,
            "department": "Central Recruitment & Promotion Department (CRPD)",
            "advertisement_number": "",
            "notification_number": "",
            "vacancies": None,
            "salary_text": "₹41,960 - ₹63,840 (PO) / ₹19,900 - ₹47,920 (Clerk)",
            "qualification": "Graduation in any discipline from a recognized University",
            "last_date": None,
            "apply_url": "https://sbi.co.in/web/careers",
            "notification_pdf_url": url if url.lower().endswith(".pdf") else None,
            "official_source": self.domain,
            "raw_content": soup.get_text(separator=" ", strip=True)[:4000]
        }
