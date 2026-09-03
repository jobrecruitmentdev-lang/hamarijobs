import os
import hashlib
import re
import requests
from typing import Optional, Dict, Any, List
import fitz  # PyMuPDF
from automation.config import settings, BASE_DIR
from automation.logger import logger
from automation.ocr.image_ocr import ImageOCR

class PDFParser:
    """
    Production-grade PDF Document Processing Pipeline.
    Extracts text, calculates SHA-256 hashes, detects scanned pages,
    falls back to bilingual OCR, and extracts structured sections.
    """
    
    def __init__(self):
        self.image_ocr = ImageOCR()
        
    def download_pdf(self, url: str, destination_path: Optional[str] = None) -> Optional[str]:
        """
        Downloads a PDF from an official government URL and returns its local file path.
        """
        try:
            if not destination_path:
                url_hash = hashlib.sha256(url.encode()).hexdigest()[:16]
                filename = f"notice_{url_hash}.pdf"
                destination_path = os.path.join(BASE_DIR, settings.PDF_STORAGE_DIR, filename)

            os.makedirs(os.path.dirname(destination_path), exist_ok=True)
            
            headers = {
                "User-Agent": settings.USER_AGENT,
                "Accept": "application/pdf,*/*"
            }
            
            # Streaming download with SSL resilience
            response = requests.get(url, stream=True, headers=headers, timeout=45, verify=False)
            response.raise_for_status()
            
            with open(destination_path, "wb") as f:
                for chunk in response.iter_content(chunk_size=16384):
                    if chunk:
                        f.write(chunk)
                        
            logger.info(f"[PDFParser] Downloaded PDF ({os.path.getsize(destination_path)} bytes) to {destination_path}")
            return destination_path

        except Exception as e:
            logger.error(f"[PDFParser] Failed to download PDF from {url}: {e}")
            return None

    def compute_sha256(self, file_path: str) -> Optional[str]:
        """
        Calculates cryptographic SHA-256 hash of the PDF file.
        """
        if not os.path.exists(file_path):
            return None
        sha256_hash = hashlib.sha256()
        with open(file_path, "rb") as f:
            for byte_block in iter(lambda: f.read(65536), b""):
                sha256_hash.update(byte_block)
        return sha256_hash.hexdigest()

    def process_document(self, file_path: str, max_pages: int = 50) -> Dict[str, Any]:
        """
        Comprehensive document extraction:
        1. PyMuPDF digital text extraction.
        2. Scanned page detection & OCR fallback.
        3. Document hashing & metadata.
        4. Key section segmentation.
        """
        if not os.path.exists(file_path):
            return {
                "success": False,
                "error": f"File not found at {file_path}",
                "raw_text": "",
                "sha256": None,
                "page_count": 0
            }

        try:
            sha256 = self.compute_sha256(file_path)
            doc = fitz.open(file_path)
            total_pages = len(doc)
            pages_to_process = min(total_pages, max_pages)

            page_texts = []
            scanned_pages_count = 0

            for page_idx in range(pages_to_process):
                page = doc.load_page(page_idx)
                text = page.get_text("text").strip()

                # Text density check: If fewer than 40 chars on page, it's likely a scanned image
                if len(text) < 40:
                    scanned_pages_count += 1
                    # Render pixmap for this specific page and run OCR
                    pix = page.get_pixmap(matrix=fitz.Matrix(2.0, 2.0))
                    from PIL import Image
                    img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)
                    ocr_res = self.image_ocr.extract_from_image(img)
                    text = ocr_res.get("text", "")

                page_texts.append(f"=== PAGE {page_idx + 1} OF {total_pages} ===\n{text}")

            doc.close()
            full_text = "\n\n".join(page_texts)

            # Extract structured sections
            sections = self.segment_sections(full_text)

            is_scanned = (scanned_pages_count / max(1, pages_to_process)) > 0.5

            return {
                "success": True,
                "sha256": sha256,
                "file_path": file_path,
                "total_pages": total_pages,
                "processed_pages": pages_to_process,
                "is_scanned": is_scanned,
                "raw_text": full_text,
                "sections": sections,
                "text_length": len(full_text)
            }

        except Exception as e:
            logger.error(f"[PDFParser] Error processing PDF {file_path}: {e}")
            return {
                "success": False,
                "error": str(e),
                "raw_text": "",
                "sha256": self.compute_sha256(file_path),
                "page_count": 0
            }

    def segment_sections(self, text: str) -> Dict[str, str]:
        """
        Segments the raw extracted text into high-priority topic sections
        to feed targeted prompts to the LLM without exceeding context limits.
        """
        sections = {
            "important_dates": "",
            "vacancies_and_posts": "",
            "eligibility_and_qualification": "",
            "age_limit": "",
            "salary_and_pay": "",
            "application_fee": "",
            "selection_and_exam_pattern": "",
            "syllabus": ""
        }

        # Regex heuristics for section boundary detection
        patterns = {
            "important_dates": r"(?:important\s+dates|schedule|crucial\s+dates|dates\s+to\s+remember|submission\s+of\s+online\s+applications)(.*?)(?=(?:vacanc|eligib|age\s+limit|pay\s+scale|scheme\s+of\s+exam|\Z))",
            "vacancies_and_posts": r"(?:vacancies|details\s+of\s+posts|post\s+details|tentative\s+vacancies|break-up\s+of\s+vacancies)(.*?)(?=(?:eligib|age\s+limit|pay\s+scale|fee|scheme\s+of\s+exam|\Z))",
            "eligibility_and_qualification": r"(?:essential\s+educational\s+qualification|educational\s+qualification|eligibility\s+criteria|minimum\s+qualification)(.*?)(?=(?:age\s+limit|pay\s+scale|fee|scheme\s+of\s+exam|\Z))",
            "age_limit": r"(?:age\s+limit|age\s+criteria|relaxation\s+in\s+upper\s+age\s+limit)(.*?)(?=(?:pay\s+scale|fee|scheme\s+of\s+exam|how\s+to\s+apply|\Z))",
            "salary_and_pay": r"(?:pay\s+scale|remuneration|salary|pay\s+level|emoluments)(.*?)(?=(?:fee|how\s+to\s+apply|scheme\s+of\s+exam|syllabus|\Z))",
            "application_fee": r"(?:application\s+fee|fee\s+payable|mode\s+of\s+payment)(.*?)(?=(?:centre\s+of\s+examination|scheme\s+of\s+exam|how\s+to\s+apply|\Z))",
            "selection_and_exam_pattern": r"(?:scheme\s+of\s+examination|selection\s+process|mode\s+of\s+selection|tier-i\s+examination)(.*?)(?=(?:indicative\s+syllabus|syllabus|general\s+instructions|\Z))",
            "syllabus": r"(?:indicative\s+syllabus|detailed\s+syllabus|syllabus\s+for\s+examination)(.*?)(?=(?:general\s+instructions|how\s+to\s+apply|annexure|\Z))"
        }

        for sec_name, pattern in patterns.items():
            match = re.search(pattern, text, re.IGNORECASE | re.DOTALL)
            if match:
                sections[sec_name] = match.group(1).strip()[:3000]

        return sections
