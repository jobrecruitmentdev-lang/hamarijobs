from typing import List, Dict, Any
from bs4 import BeautifulSoup
from automation.adapters.base import BaseAdapter

class DefenceAdapter(BaseAdapter):
    """
    Unified Adapter for Indian Armed Forces and Defence Research Organizations:
    - Indian Army (joinindianarmy.nic.in)
    - Indian Navy (joinindiannavy.gov.in)
    - Indian Air Force (afcat.cdac.in / careerindianairforce.cdac.in)
    - DRDO (drdo.gov.in)
    - ISRO (isro.gov.in)
    - BARC (barc.gov.in)
    """
    
    source_name: str = "Defence & Research"
    domain: str = "joinindianarmy.nic.in"
    organization: str = "Ministry of Defence, Govt of India"
    source_type: str = "Defense"
    priority: str = "Critical"
    requires_js: bool = False
    uses_pdf: bool = True
    
    def get_target_urls(self) -> List[str]:
        return [
            "https://www.joinindiannavy.gov.in/en/page/current-events.html",
            "https://afcat.cdac.in/AFCAT/",
            "https://www.drdo.gov.in/drdo/careers",
            "https://www.isro.gov.in/Careers.html",
            "https://recruit.barc.gov.in/barcrecruit/"
        ]
        
    def extract_job_links(self, html_content: str, base_url: str = "") -> List[str]:
        soup = BeautifulSoup(html_content, "html.parser")
        job_links = []
        
        for a_tag in soup.find_all("a", href=True):
            href = a_tag["href"].strip()
            text = a_tag.get_text(strip=True).lower()
            keywords = ["recruitment", "officer", "agniveer", "afcat", "scientist", "fellow", "apprentice", ".pdf", "advt"]
            if any(k in href.lower() or k in text for k in keywords):
                full_url = self.normalize_url(href, base_url)
                if full_url not in job_links:
                    job_links.append(full_url)
                    
        return job_links

    def parse_job_details(self, html_content: str, url: str) -> Dict[str, Any]:
        soup = BeautifulSoup(html_content, "html.parser")
        title = soup.find("h1") or soup.find("h2") or soup.find("title")
        title_text = title.get_text(strip=True) if title else "Defence & Research Recruitment Opening"
        
        return {
            "title": title_text,
            "organization": self.organization,
            "department": "Department of Defence / Space / Atomic Energy",
            "advertisement_number": "",
            "notification_number": "",
            "vacancies": None,
            "salary_text": "₹56,100+ (Commissioned Officer / Scientist) / ₹30,000 (Agniveer)",
            "qualification": "10th / 12th / B.E. / B.Tech / M.Sc / Graduation",
            "last_date": None,
            "apply_url": url,
            "notification_pdf_url": url if url.lower().endswith(".pdf") else None,
            "official_source": self.domain,
            "raw_content": soup.get_text(separator=" ", strip=True)[:4000]
        }
