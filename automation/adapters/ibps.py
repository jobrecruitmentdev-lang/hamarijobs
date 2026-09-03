from typing import List, Dict, Any
from bs4 import BeautifulSoup
from automation.adapters.base import BaseAdapter

class IBPSAdapter(BaseAdapter):
    """
    Adapter for Institute of Banking Personnel Selection (IBPS).
    Official Domain: ibps.in
    Monitors CRP PO/MT, CRP Clerk, CRP Specialist Officers, and CRP RRBs (Officers & Office Assistants).
    """
    
    source_name: str = "IBPS"
    domain: str = "ibps.in"
    organization: str = "Institute of Banking Personnel Selection"
    source_type: str = "Bank"
    priority: str = "High"
    requires_js: bool = False
    uses_pdf: bool = True
    
    def get_target_urls(self) -> List[str]:
        return [
            "https://www.ibps.in/careers/",
            "https://www.ibps.in/crp-po-mt/",
            "https://www.ibps.in/crp-clerical-cadre/",
            "https://www.ibps.in/crp-specialist-officers/",
            "https://www.ibps.in/crp-rrbs/"
        ]
        
    def extract_job_links(self, html_content: str, base_url: str = "https://www.ibps.in") -> List[str]:
        soup = BeautifulSoup(html_content, "html.parser")
        job_links = []
        
        for a_tag in soup.find_all("a", href=True):
            href = a_tag["href"].strip()
            text = a_tag.get_text(strip=True).lower()
            if ".pdf" in href.lower() or "crp" in href.lower() or "notification" in text or "advertisement" in text:
                full_url = self.normalize_url(href, base_url)
                if full_url not in job_links:
                    job_links.append(full_url)
                    
        return job_links

    def parse_job_details(self, html_content: str, url: str) -> Dict[str, Any]:
        soup = BeautifulSoup(html_content, "html.parser")
        title = soup.find("h1") or soup.find("h2") or soup.find("title")
        title_text = title.get_text(strip=True) if title else "IBPS Common Recruitment Process (CRP)"
        
        return {
            "title": title_text,
            "organization": self.organization,
            "department": "Public Sector Banks & Regional Rural Banks",
            "advertisement_number": "",
            "notification_number": "",
            "vacancies": None,
            "salary_text": "Bank Clerk (₹19,900+) / Bank PO (₹36,000 - ₹63,840) + Allowances",
            "qualification": "Graduation in any stream / Professional Degree for Specialist Officers",
            "last_date": None,
            "apply_url": "https://www.ibps.in",
            "notification_pdf_url": url if url.lower().endswith(".pdf") else None,
            "official_source": self.domain,
            "raw_content": soup.get_text(separator=" ", strip=True)[:4000]
        }
