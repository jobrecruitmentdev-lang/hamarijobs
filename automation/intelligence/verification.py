import re
import datetime
from typing import Dict, Any, List, Tuple
from automation.logger import logger

class FactVerificationShield:
    """
    Fact-Verification Double-Shield & Anti-Hallucination Engine.
    Validates AI-extracted recruitment structures against strict deterministic business rules.
    If an anomaly or suspicious data is detected, flags the record for operator review.
    """

    ALLOWED_DOMAINS_SUFFIXES = (
        '.gov.in', '.nic.in', '.ac.in', '.edu.in', '.res.in',
        'ibps.in', 'sbi.co.in', 'rrbapply.gov.in', 'upsconline.nic.in',
        'ssc.gov.in', 'afcat.cdac.in'
    )

    @classmethod
    def verify_recruitment_data(cls, extracted_data: Any, meta: Dict[str, Any]) -> Tuple[str, List[str]]:
        """
        Runs comprehensive validation checks.
        Returns: (review_status: 'VERIFIED' | 'REVIEW_PENDING', anomaly_flags: List[str])
        """
        flags = []

        # 1. Total Vacancies Sanity Check
        vacancies = getattr(extracted_data, 'total_vacancies', None)
        if vacancies is None or not isinstance(vacancies, int) or vacancies <= 0:
            flags.append("ANOMALY_INVALID_VACANCY_COUNT")
        elif vacancies > 150000:
            flags.append("ANOMALY_UNREALISTIC_VACANCY_COUNT")

        # 2. Official Domain Verification
        official_url = meta.get('apply_url', '') or meta.get('domain', '')
        if official_url:
            clean_url = official_url.lower()
            is_valid_domain = any(suffix in clean_url for suffix in cls.ALLOWED_DOMAINS_SUFFIXES)
            if not is_valid_domain and not ('cdac.in' in clean_url or 'indianrailways.gov.in' in clean_url):
                flags.append("ANOMALY_UNVERIFIED_APPLY_DOMAIN")
        else:
            flags.append("ANOMALY_MISSING_OFFICIAL_URL")

        # 3. Dates Timeline Validation
        important_dates = getattr(extracted_data, 'important_dates', None)
        if important_dates:
            start_date_str = getattr(important_dates, 'application_start_date', None)
            end_date_str = getattr(important_dates, 'application_last_date', None)

            def parse_date(d_str):
                if not d_str:
                    return None
                for fmt in ('%Y-%m-%d', '%d-%m-%Y', '%d/%m/%Y', '%Y/%m/%d'):
                    try:
                        return datetime.datetime.strptime(d_str.strip(), fmt).date()
                    except (ValueError, AttributeError):
                        pass
                # Regex match
                m = re.search(r'(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})', str(d_str))
                if m:
                    try:
                        return datetime.date(int(m.group(3)), int(m.group(2)), int(m.group(1)))
                    except ValueError:
                        pass
                return None

            d_start = parse_date(start_date_str)
            d_end = parse_date(end_date_str)

            if d_start and d_end and d_end < d_start:
                flags.append("ANOMALY_DEADLINE_BEFORE_START_DATE")

        # 4. Age Limit Boundaries Check
        age_limit = getattr(extracted_data, 'age_limit', None)
        if age_limit:
            min_age = getattr(age_limit, 'min_age', None)
            max_age = getattr(age_limit, 'max_age', None)
            if min_age is not None and (min_age < 16 or min_age > 50):
                flags.append("ANOMALY_SUSPICIOUS_MIN_AGE")
            if max_age is not None and (max_age < 18 or max_age > 65):
                flags.append("ANOMALY_SUSPICIOUS_MAX_AGE")
            if min_age and max_age and min_age > max_age:
                flags.append("ANOMALY_MIN_AGE_EXCEEDS_MAX_AGE")

        # Decision
        if flags:
            logger.warning(f"⚠️ [VerificationShield] Flagged anomalies: {', '.join(flags)}")
            return "REVIEW_PENDING", flags
        
        return "VERIFIED", []
