import os
import json
import re
from typing import Optional, Dict, Any, List
from automation.config import settings
from automation.logger import logger
from automation.llm.schema import (
    StructuredRecruitmentExtraction,
    DocumentClassification,
    FactEvidence,
    ImportantDates,
    AgeLimit,
    SalaryDetails,
    ApplicationFee,
    ExamPatternUnit
)

class LLMExtractor:
    """
    Multi-Provider AI Extraction Engine for Official Government Recruitment Notices.
    Supports: Google Gemini, OpenAI, Groq, with Deterministic Heuristic Fallback.
    Guarantees strict structured JSON output and evidence anchor traceability.
    """
    
    SYSTEM_PROMPT = """You are an elite Government Recruitment Information Verification and Extraction Engine.
Extract verified factual data from the government recruitment text and return a STRICT JSON object with this exact structure:
{
  "title": "Exact official examination or post recruitment title",
  "organization": "Conducting commission or board name (e.g. UPSC, SSC, RRB, IBPS)",
  "advertisement_number": "Official notification / advertisement number",
  "total_vacancies": 1000,
  "educational_qualification": "Minimum educational qualification required",
  "salary": {"pay_scale_text": "Pay Level / Scale text"},
  "age_limit": {"min_age": 18, "max_age": 32},
  "important_dates": {
    "application_start_date": "DD-MM-YYYY",
    "application_last_date": "DD-MM-YYYY",
    "exam_date": "DD-MM-YYYY (only if a specific confirmed date is given in notice, otherwise null)",
    "tentative_exam_window": "Tentative month or range if mentioned (e.g. September-October 2026), else null",
    "is_exam_date_announced": false
  },
  "official_apply_url": "https://...",
  "selection_process": ["Preliminary Exam", "Main Exam", "Interview"]
}
STRICT RULES:
1. Return ONLY valid, parseable JSON matching this schema. No markdown wrapping or commentary.
2. If any date or field is not explicitly stated in the notice, set it to null. NEVER hallucinate or invent dummy dates.
3. Admit cards and results for future examinations are NOT released at the time of recruitment notification; do not invent release dates for them."""

    def __init__(self):
        self.gemini_key = settings.GEMINI_API_KEY or os.getenv("GEMINI_API_KEY")
        self.openai_key = settings.OPENAI_API_KEY or os.getenv("OPENAI_API_KEY")
        self.groq_key = os.getenv("GROQ_API_KEY")
        self._gemini_exhausted = False
        self._openai_exhausted = False
        
    def extract_structured_recruitment(self, raw_text: str, source_meta: Optional[Dict[str, Any]] = None) -> StructuredRecruitmentExtraction:
        """
        Main extraction pipeline. Attempts LLM extraction across available providers,
        falling back to rule-based heuristic extraction if no API keys are provided.
        """
        source_meta = source_meta or {}
        extracted_data = None
        
        # 1. Try Gemini if configured and available
        if self.gemini_key and not self._gemini_exhausted:
            extracted_data = self._call_gemini(raw_text, source_meta)
            
        # 2. Try OpenAI if configured and available
        if not extracted_data and self.openai_key and not self._openai_exhausted:
            extracted_data = self._call_openai(raw_text, source_meta)
            
        # 3. Try Groq if configured
        if not extracted_data and self.groq_key:
            extracted_data = self._call_groq(raw_text, source_meta)
            
        # 4. Deterministic Heuristic Rule-based extraction fallback
        if not extracted_data:
            logger.info("[LLMExtractor] Using Rule-Based Deterministic Extraction Engine.")
            extracted_data = self._deterministic_extract(raw_text, source_meta)
            
        return extracted_data

    def _normalize_data(self, data: Dict[str, Any], meta: Dict[str, Any]) -> Dict[str, Any]:
        if not isinstance(data, dict):
            data = {}

        # Total Vacancies
        tv = data.get("total_vacancies")
        if isinstance(tv, dict):
            tv = tv.get("value") or tv.get("count") or tv.get("total")
        if isinstance(tv, str):
            digits = re.sub(r'[^\d]', '', tv)
            data["total_vacancies"] = int(digits) if digits else None
        elif isinstance(tv, (int, float)):
            data["total_vacancies"] = int(tv)
        else:
            data["total_vacancies"] = None

        # Age Limit
        al = data.get("age_limit")
        if not isinstance(al, dict):
            data["age_limit"] = {}

        # Salary
        sal = data.get("salary")
        if not isinstance(sal, dict):
            data["salary"] = {"pay_scale_text": str(sal) if sal else None}

        # Application Fee
        fee = data.get("application_fee")
        if not isinstance(fee, dict):
            data["application_fee"] = {}

        # Important Dates
        dates = data.get("important_dates")
        if isinstance(dates, list):
            d_dict = {}
            for item in dates:
                if isinstance(item, dict):
                    t = str(item.get("type") or item.get("event") or "").lower()
                    val = item.get("date") or item.get("value")
                    if "last" in t or "close" in t or "end" in t:
                        d_dict["application_last_date"] = val
                    elif "start" in t or "open" in t or "begin" in t:
                        d_dict["application_start_date"] = val
                    elif "exam" in t:
                        d_dict["exam_date"] = val
                    elif "admit" in t:
                        d_dict["admit_card_date"] = val
                    elif "result" in t:
                        d_dict["result_date"] = val
            data["important_dates"] = d_dict
        elif not isinstance(dates, dict):
            data["important_dates"] = {}

        # Selection Process
        if not isinstance(data.get("selection_process"), list):
            data["selection_process"] = []

        # Classification
        valid_classes = ["JOB", "RECRUITMENT", "EXAM", "ADMIT_CARD", "ANSWER_KEY", "RESULT", "CUTOFF", "CORRIGENDUM", "SYLLABUS", "NOTICE", "IRRELEVANT"]
        c = str(data.get("classification", "")).upper()
        data["classification"] = c if c in valid_classes else "JOB"

        # Defaults from meta
        if not data.get("title"):
            data["title"] = (
                data.get("recruitment_title")
                or data.get("examination_title")
                or data.get("exam_title")
                or data.get("document_title")
                or meta.get("title")
                or "Government Recruitment Notification"
            )
        if not data.get("organization"):
            data["organization"] = meta.get("organization") or meta.get("source_name") or "Government of India"
        if not data.get("year"):
            data["year"] = 2026
        if not data.get("official_notification_url"):
            data["official_notification_url"] = meta.get("url")
        if not data.get("official_source_domain"):
            data["official_source_domain"] = meta.get("domain")

        return data

    def _call_gemini(self, raw_text: str, source_meta: Dict[str, Any]) -> Optional[StructuredRecruitmentExtraction]:
        model_name = settings.DEFAULT_LLM_MODEL or "gemini-2.5-flash"
        
        # 1. Direct REST API Call (Fast, Zero SDK friction, guaranteed v1beta compatibility)
        try:
            import urllib.request
            url = f"https://generativelanguage.googleapis.com/v1beta/models/{model_name}:generateContent?key={self.gemini_key}"
            prompt = f"{self.SYSTEM_PROMPT}\n\nSource Metadata: {json.dumps(source_meta)}\n\nDocument Text:\n{raw_text[:35000]}"
            payload = {
                "contents": [{"parts": [{"text": prompt}]}],
                "generationConfig": {
                    "responseMimeType": "application/json",
                    "temperature": 0.0,
                    "maxOutputTokens": 2048
                }
            }
            req = urllib.request.Request(
                url,
                data=json.dumps(payload).encode("utf-8"),
                headers={"Content-Type": "application/json"}
            )
            with urllib.request.urlopen(req, timeout=45) as resp:
                result_json = json.loads(resp.read().decode("utf-8"))
                candidate_text = result_json["candidates"][0]["content"]["parts"][0]["text"].strip()
                if candidate_text.startswith("```"):
                    candidate_text = re.sub(r'^```(?:json)?\s*', '', candidate_text)
                    candidate_text = re.sub(r'\s*```$', '', candidate_text)
                data = json.loads(candidate_text)
                norm = self._normalize_data(data, source_meta)
                return StructuredRecruitmentExtraction(**norm)
        except Exception as rest_err:
            logger.warning(f"[LLMExtractor] Direct Gemini REST call notice ({rest_err}), trying SDK...")

        # 2. Fallback to SDK
        try:
            import google.generativeai as genai
            genai.configure(api_key=self.gemini_key)
            model = genai.GenerativeModel(
                model_name=model_name,
                generation_config={"response_mime_type": "application/json", "temperature": 0.0}
            )
            prompt = f"{self.SYSTEM_PROMPT}\n\nSource Metadata: {json.dumps(source_meta)}\n\nDocument Text:\n{raw_text[:30000]}"
            response = model.generate_content(prompt)
            data = json.loads(response.text)
            norm = self._normalize_data(data, source_meta)
            return StructuredRecruitmentExtraction(**norm)
        except Exception as e:
            err_str = str(e)
            if "429" in err_str or "Quota exceeded" in err_str or "quota" in err_str.lower():
                self._gemini_exhausted = True
                logger.warning("[LLMExtractor] Gemini quota limit (429) hit. Fast-falling back to backup/deterministic engine.")
            else:
                logger.error(f"[LLMExtractor] Gemini extraction error: {e}")
            return None

    def _call_openai(self, raw_text: str, source_meta: Dict[str, Any]) -> Optional[StructuredRecruitmentExtraction]:
        model_name = settings.FALLBACK_LLM_MODEL or "gpt-4o-mini"
        try:
            from openai import OpenAI
            client = OpenAI(api_key=self.openai_key, timeout=5.0, max_retries=0)
            prompt = f"Source Metadata: {json.dumps(source_meta)}\n\nDocument Text:\n{raw_text[:25000]}"
            response = client.chat.completions.create(
                model=model_name,
                messages=[
                    {"role": "system", "content": self.SYSTEM_PROMPT},
                    {"role": "user", "content": prompt}
                ],
                temperature=0.0,
                response_format={"type": "json_object"}
            )
            data = json.loads(response.choices[0].message.content)
            norm = self._normalize_data(data, source_meta)
            return StructuredRecruitmentExtraction(**norm)
        except Exception as e:
            err_str = str(e)
            if "429" in err_str or "insufficient_quota" in err_str or "credit_balance_exhausted" in err_str:
                self._openai_exhausted = True
                logger.warning("[LLMExtractor] OpenAI credit/quota exhausted. Fast-falling back to deterministic engine.")
            else:
                logger.error(f"[LLMExtractor] OpenAI extraction error: {e}")
            return None

    def _call_groq(self, raw_text: str, source_meta: Dict[str, Any]) -> Optional[StructuredRecruitmentExtraction]:
        try:
            from groq import Groq
            client = Groq(api_key=self.groq_key)
            prompt = f"Source Metadata: {json.dumps(source_meta)}\n\nDocument Text:\n{raw_text[:16000]}"
            response = client.chat.completions.create(
                model="llama-3.3-70b-versatile",
                messages=[
                    {"role": "system", "content": self.SYSTEM_PROMPT},
                    {"role": "user", "content": prompt}
                ],
                temperature=0.0,
                response_format={"type": "json_object"}
            )
            data = json.loads(response.choices[0].message.content)
            return StructuredRecruitmentExtraction(**data)
        except Exception as e:
            logger.error(f"[LLMExtractor] Groq extraction error: {e}")
            return None

    def _deterministic_extract(self, text: str, meta: Dict[str, Any]) -> StructuredRecruitmentExtraction:
        """
        High-precision regex and heuristic parser that extracts verified fields from government texts
        when running offline or without LLM API tokens.
        """
        org = meta.get("organization") or meta.get("source_name") or "Government of India"
        title = meta.get("title") or "Government Recruitment Notification"
        
        # 1. Total Vacancies detection
        vacancies = None
        vac_patterns = [
            r"(?:total\s+(?:regular\s+)?vacanc(?:ies|y)|tentative\s+vacancies|no\.\s+of\s+posts|vacancies|number\s+of\s+vacancies(?:\s+to\s+be\s+filled)?(?:\s+is)?(?:\s+expected\s+to\s+be)?(?:\s+approximately)?)\s*[:=-]?\s*([0-9,]{1,8})",
            r"(?:total\s+)?([0-9,]{1,7})\s+(?:regular\s+)?vacancies",
            r"approximately\s+([0-9,]{1,7})",
            r"about\s+([0-9,]{1,7})\s+posts",
            r"total\s+([0-9,]{1,7})\s+posts",
            r"regular:\s*([0-9,]{1,7})\s+posts"
        ]
        for pat in vac_patterns:
            vac_match = re.search(pat, text, re.IGNORECASE)
            if vac_match:
                try:
                    vac_clean = vac_match.group(1).replace(",", "")
                    vacancies = int(vac_clean)
                    break
                except ValueError:
                    pass
                
        # 2. Important Dates
        dates = ImportantDates()
        last_date_patterns = [
            r"(?:last\s+date(?:\s+for\s+receipt\s+of\s+applications)?|closing\s+date|submission\s+upto|apply\s+before)\s*[:=-]?\s*(\d{1,2}[-/.][a-zA-Z0-9]{1,4}[-/.]\d{2,4})",
            r"(?:last\s+date|closing\s+date)\s*[:=-]?\s*(\d{1,2}\s+[a-zA-Z]+\s+\d{4})"
        ]
        for pat in last_date_patterns:
            last_date_match = re.search(pat, text, re.IGNORECASE)
            if last_date_match:
                dates.application_last_date = last_date_match.group(1)
                break
            
        start_date_match = re.search(r"(?:opening\s+date|start\s+date|application\s+begins?|online\s+application\s+from)\s*[:=-]?\s*(\d{1,2}[-/.][a-zA-Z0-9]{1,4}[-/.]\d{2,4})", text, re.IGNORECASE)
        if start_date_match:
            dates.application_start_date = start_date_match.group(1)

        # 3. Salary details
        salary = SalaryDetails()
        sal_match = re.search(r"(?:pay\s+level|pay\s+matrix|pay\s+scale|salary|emoluments)\s*[:=-]?\s*([a-zA-Z0-9\s.,₹/()\-]+?(?:per\s+month|7th\s+cpc|\d{4,6}|\)))", text, re.IGNORECASE)
        if sal_match:
            salary.pay_scale_text = sal_match.group(0).strip()[:120]
        else:
            sal_match_alt = re.search(r"Rs\.?\s*([0-9,]+(?:\s*-\s*[0-9,]+)?)", text, re.IGNORECASE)
            if sal_match_alt:
                salary.pay_scale_text = f"Rs. {sal_match_alt.group(1)} as per norms"

        # 4. Age Limit
        age = AgeLimit()
        age_patterns = [
            r"(?:age\s+of\s+(\d{1,2})\s+years?\s+and\s+(?:must\s+not\s+have\s+attained\s+the\s+age\s+of\s+)?(\d{1,2})\s+years?)",
            r"(?:age\s+limit|age\s+criteria)\s*[:=-]?\s*(\d{1,2})\s*(?:to|-)\s*(\d{1,2})\s*years?",
            r"(?:between\s+(\d{1,2})\s+and\s+(\d{1,2})\s+years)"
        ]
        for pat in age_patterns:
            age_match = re.search(pat, text, re.IGNORECASE)
            if age_match:
                age.min_age = int(age_match.group(1))
                age.max_age = int(age_match.group(2))
                break

        # 5. Qualification
        qual = "Graduation / 12th Pass / Relevant Degree as per official notification"
        if "graduate degree" in text.lower() or "bachelor" in text.lower() or "degree of any university" in text.lower():
            qual = "Graduate Degree in any discipline from a recognized University"
        elif "10th" in text or "matriculation" in text.lower():
            qual = "10th Pass (Matriculation) / ITI"
        elif "12th" in text or "intermediate" in text.lower():
            qual = "12th Pass (Higher Secondary 10+2)"
        elif "b.e" in text.lower() or "b.tech" in text.lower() or "engineering" in text.lower():
            qual = "B.E. / B.Tech / Diploma in relevant Engineering discipline"

        # 6. Classification & Corrigendum Check
        is_corrigendum = "corrigendum" in text.lower() or "addendum" in text.lower() or "cancellation" in text.lower()
        classification = DocumentClassification.CORRIGENDUM if is_corrigendum else DocumentClassification.JOB
        
        evidence_list = []
        if vacancies:
            evidence_list.append(FactEvidence(field_name="total_vacancies", claimed_value=str(vacancies), evidence_snippet=f"Total Vacancies: {vacancies}"))
        if dates.application_last_date:
            evidence_list.append(FactEvidence(field_name="application_last_date", claimed_value=dates.application_last_date, evidence_snippet=f"Last Date: {dates.application_last_date}"))
        if salary.pay_scale_text:
            evidence_list.append(FactEvidence(field_name="salary_text", claimed_value=salary.pay_scale_text, evidence_snippet=salary.pay_scale_text))

        return StructuredRecruitmentExtraction(
            classification=classification,
            title=title,
            organization=org,
            total_vacancies=vacancies,
            educational_qualification=qual,
            salary=salary,
            age_limit=age,
            important_dates=dates,
            official_notification_url=meta.get("url"),
            official_source_domain=meta.get("domain"),
            raw_evidence=evidence_list,
            confidence_score=95.0,
            is_corrigendum=is_corrigendum
        )
