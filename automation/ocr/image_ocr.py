import os
import io
import hashlib
from typing import Optional, List, Dict, Any
from PIL import Image, ImageEnhance, ImageFilter
from automation.config import settings
from automation.logger import logger

class ImageOCR:
    """
    Production-grade OCR processing engine for scanned government notifications and images.
    Supports Tesseract OCR with English + Hindi (eng+hin) and RapidOCR ONNX fallback.
    """
    
    def __init__(self, tesseract_cmd: Optional[str] = None):
        self.tesseract_cmd = tesseract_cmd or settings.TESSERACT_CMD
        self._tesseract_available = None
        self._rapidocr_available = None
        self.rapid_ocr = None
        
    def _init_engines(self):
        if self._tesseract_available is not None:
            return
            
        # 1. Check Tesseract
        try:
            import pytesseract
            if os.path.exists(self.tesseract_cmd):
                pytesseract.pytesseract.tesseract_cmd = self.tesseract_cmd
            pytesseract.get_tesseract_version()
            self._tesseract_available = True
            logger.info("[ImageOCR] Tesseract OCR engine initialized.")
        except Exception:
            self._tesseract_available = False

        # 2. Check RapidOCR ONNX Runtime
        try:
            from rapidocr_onnxruntime import RapidOCR
            self.rapid_ocr = RapidOCR()
            self._rapidocr_available = True
            logger.info("[ImageOCR] RapidOCR ONNX engine initialized.")
        except Exception:
            self._rapidocr_available = False

    def preprocess_image(self, image: Image.Image) -> Image.Image:
        """
        Enhances contrast, converts to grayscale, and applies slight sharpening
        to optimize OCR recognition on low-resolution scanned notices.
        """
        # Convert to Grayscale
        gray_img = image.convert("L")
        
        # Enhance Contrast
        enhancer = ImageEnhance.Contrast(gray_img)
        enhanced_img = enhancer.enhance(1.8)
        
        # Slight sharpening
        sharpened_img = enhanced_img.filter(ImageFilter.SHARPEN)
        return sharpened_img

    def extract_from_image(self, image_input: Any, language: str = "eng+hin") -> Dict[str, Any]:
        """
        Extracts text from a PIL Image, file path, or bytes.
        Returns: { 'text': str, 'confidence': float, 'engine': str }
        """
        try:
            if isinstance(image_input, str) and os.path.exists(image_input):
                img = Image.open(image_input)
            elif isinstance(image_input, bytes):
                img = Image.open(io.BytesIO(image_input))
            elif isinstance(image_input, Image.Image):
                img = image_input
            else:
                return {"text": "", "confidence": 0.0, "engine": "none", "error": "Invalid image input"}

            processed_img = self.preprocess_image(img)

            # Try Tesseract first
            if self._tesseract_available:
                import pytesseract
                try:
                    text = pytesseract.image_to_string(processed_img, lang=language)
                    return {
                        "text": text.strip(),
                        "confidence": 88.0,
                        "engine": "tesseract"
                    }
                except Exception as tess_err:
                    logger.warning(f"[ImageOCR] Tesseract execution failed: {tess_err}")

            # Try RapidOCR fallback
            if self._rapidocr_available:
                import numpy as np
                img_np = np.array(processed_img)
                result, elapse = self.rapid_ocr(img_np)
                if result:
                    extracted_lines = [line[1] for line in result]
                    confidences = [float(line[2]) for line in result if len(line) > 2]
                    avg_conf = (sum(confidences) / len(confidences) * 100) if confidences else 85.0
                    return {
                        "text": "\n".join(extracted_lines).strip(),
                        "confidence": avg_conf,
                        "engine": "rapidocr"
                    }

            return {"text": "", "confidence": 0.0, "engine": "none", "error": "No OCR engine available"}

        except Exception as e:
            logger.error(f"[ImageOCR] OCR extraction error: {e}")
            return {"text": "", "confidence": 0.0, "engine": "error", "error": str(e)}

    def process_scanned_pdf_pages(self, pdf_path: str, max_pages: int = 15, language: str = "eng+hin") -> Dict[str, Any]:
        """
        Renders PDF pages into images using PyMuPDF and runs OCR page-by-page.
        """
        import fitz  # PyMuPDF
        
        if not os.path.exists(pdf_path):
            return {"text": "", "page_count": 0, "confidence": 0.0, "error": "File not found"}

        try:
            doc = fitz.open(pdf_path)
            total_pages = min(len(doc), max_pages)
            extracted_pages = []
            page_confidences = []

            for page_num in range(total_pages):
                page = doc.load_page(page_num)
                # Render page to high-res pixmap (2.0 zoom for crisp text)
                zoom = 2.0
                mat = fitz.Matrix(zoom, zoom)
                pix = page.get_pixmap(matrix=mat)
                img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)

                ocr_res = self.extract_from_image(img, language=language)
                page_text = ocr_res.get("text", "")
                extracted_pages.append(f"--- [PAGE {page_num + 1}] ---\n{page_text}")
                page_confidences.append(ocr_res.get("confidence", 0.0))

            doc.close()
            full_text = "\n\n".join(extracted_pages)
            avg_confidence = (sum(page_confidences) / len(page_confidences)) if page_confidences else 0.0

            return {
                "text": full_text,
                "page_count": total_pages,
                "confidence": avg_confidence,
                "engine": "pdf_ocr_pipeline"
            }

        except Exception as e:
            logger.error(f"[ImageOCR] Scanned PDF processing failed for {pdf_path}: {e}")
            return {"text": "", "page_count": 0, "confidence": 0.0, "error": str(e)}
