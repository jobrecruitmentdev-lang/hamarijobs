from abc import ABC, abstractmethod
from typing import List, Dict, Any, Optional
from urllib.parse import urljoin, urlparse
from bs4 import BeautifulSoup
import re
from datetime import datetime

class BaseAdapter(ABC):
    """
    Abstract Base Class for all Government Source Adapters.
    Every adapter (UPSC, SSC, RRB, IBPS, Defence, StatePSC, etc.) implements this contract.
    """
    
    source_name: str = "Base"
    domain: str = ""
    organization: str = ""
    source_type: str = "Other"
    priority: str = "Medium"
    requires_js: bool = False
    uses_pdf: bool = True
    supports_rss: bool = False
    
    @abstractmethod
    def get_target_urls(self) -> List[str]:
        """
        Returns the primary recruitment and notice board URLs to scan.
        """
        pass
        
    @abstractmethod
    def extract_job_links(self, html_content: str, base_url: str = "") -> List[str]:
        """
        Extracts individual job / notification URLs from a listing page.
        """
        pass

    def discover_documents(self, html_content: str, base_url: str = "") -> List[Dict[str, Any]]:
        """
        Discovers downloadable recruitment notices (PDFs/DOCs) along with their title,
        published date, and notice number from the listing page DOM.
        """
        soup = BeautifulSoup(html_content, "html.parser")
        documents = []
        
        # Look for anchor tags pointing to PDFs or recruitment detail pages
        for a_tag in soup.find_all("a", href=True):
            href = a_tag["href"].strip()
            text = a_tag.get_text(strip=True)
            
            if not href or href.startswith("javascript:") or href.startswith("#"):
                continue
                
            absolute_url = urljoin(base_url, href) if base_url else href
            
            # Check if link or text suggests an official notification
            is_pdf = absolute_url.lower().endswith(".pdf") or ".pdf?" in absolute_url.lower()
            recruitment_keywords = [
                "recruitment", "notification", "advertisement", "advt", "notice", "cgl", "chsl",
                "examination", "apply", "vacancy", "post", "corrigendum", "admit card", "result"
            ]
            has_keywords = any(kw in text.lower() or kw in href.lower() for kw in recruitment_keywords)
            
            if (is_pdf or has_keywords) and len(text) > 4:
                # Extract potential dates
                date_match = re.search(r"\b(\d{1,2}[-/.]\d{1,2}[-/.]\d{2,4})\b", text)
                found_date = date_match.group(1) if date_match else None
                
                # Extract advertisement number pattern if any
                advt_match = re.search(r"(?:advt|notification|cen|notice|no\.?)\s*[:/]?\s*([a-zA-Z0-9/\-_.]+)", text, re.IGNORECASE)
                advt_num = advt_match.group(1) if advt_match else None
                
                documents.append({
                    "title": text,
                    "url": absolute_url,
                    "is_pdf": is_pdf,
                    "published_date": found_date,
                    "advertisement_number": advt_num,
                    "source_name": self.source_name,
                    "organization": self.organization or self.source_name
                })
                
        return documents

    def parse_job_details(self, content: str, url: str) -> Dict[str, Any]:
        """
        Default basic HTML parser. Complex documents will pass through LLM & PDF extractor.
        """
        soup = BeautifulSoup(content, "html.parser")
        title = soup.find("h1") or soup.find("title")
        title_text = title.get_text(strip=True) if title else "Government Recruitment Notice"
        
        return {
            "title": title_text,
            "organization": self.organization or self.source_name,
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

    def normalize_url(self, href: str, base_url: str) -> str:
        """
        Resolves relative URLs cleanly to absolute HTTP/HTTPS links.
        """
        if not href:
            return ""
        return urljoin(base_url, href.strip())
