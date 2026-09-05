from pydantic import BaseModel, Field
from typing import List, Dict, Optional, Any
from enum import Enum

class DocumentClassification(str, Enum):
    JOB = "JOB"
    RECRUITMENT = "RECRUITMENT"
    EXAM = "EXAM"
    ADMIT_CARD = "ADMIT_CARD"
    ANSWER_KEY = "ANSWER_KEY"
    RESULT = "RESULT"
    CUTOFF = "CUTOFF"
    CORRIGENDUM = "CORRIGENDUM"
    SYLLABUS = "SYLLABUS"
    NOTICE = "NOTICE"
    IRRELEVANT = "IRRELEVANT"

class FactEvidence(BaseModel):
    field_name: str
    claimed_value: str
    source_page: Optional[int] = None
    evidence_snippet: str
    confidence: float = Field(default=100.0, ge=0.0, le=100.0)

class ImportantDates(BaseModel):
    notification_date: Optional[str] = None
    application_start_date: Optional[str] = None
    application_last_date: Optional[str] = None
    fee_payment_last_date: Optional[str] = None
    correction_window_start: Optional[str] = None
    correction_window_end: Optional[str] = None
    admit_card_date: Optional[str] = None
    exam_date: Optional[str] = None
    tentative_exam_window: Optional[str] = None
    is_exam_date_announced: bool = False
    result_date: Optional[str] = None

class AgeLimit(BaseModel):
    min_age: Optional[int] = None
    max_age: Optional[int] = None
    as_on_date: Optional[str] = None
    age_relaxation_summary: Optional[str] = None
    category_relaxations: Dict[str, str] = Field(default_factory=dict)

class SalaryDetails(BaseModel):
    pay_level: Optional[str] = None
    pay_scale_text: Optional[str] = None
    min_salary: Optional[int] = None
    max_salary: Optional[int] = None
    grade_pay: Optional[str] = None
    in_hand_approx: Optional[str] = None

class ApplicationFee(BaseModel):
    general_obc_ews: Optional[str] = None
    sc_st_pwd: Optional[str] = None
    female: Optional[str] = None
    payment_mode: Optional[str] = None
    is_exempted: bool = False

class ExamPatternUnit(BaseModel):
    phase_name: str = "Tier 1 / Prelims"
    mode: str = "Online CBT"
    subjects: List[Dict[str, Any]] = Field(default_factory=list)
    total_questions: Optional[int] = None
    total_marks: Optional[int] = None
    duration_minutes: Optional[int] = None
    negative_marking: Optional[str] = None

class StructuredRecruitmentExtraction(BaseModel):
    classification: DocumentClassification = DocumentClassification.JOB
    title: str = Field(description="Exact official recruitment or examination title")
    organization: str = Field(description="Parent conducting organization, e.g. SSC, UPSC, RRB, SBI, DRDO")
    department: Optional[str] = None
    advertisement_number: Optional[str] = None
    notification_number: Optional[str] = None
    year: int = 2026
    post_names: List[str] = Field(default_factory=list)
    total_vacancies: Optional[int] = None
    category_wise_vacancies: Dict[str, int] = Field(default_factory=dict)
    educational_qualification: str = Field(default="", description="Minimum educational qualification")
    technical_qualification: Optional[str] = None
    experience_required: Optional[str] = None
    age_limit: AgeLimit = Field(default_factory=AgeLimit)
    salary: SalaryDetails = Field(default_factory=SalaryDetails)
    application_fee: ApplicationFee = Field(default_factory=ApplicationFee)
    important_dates: ImportantDates = Field(default_factory=ImportantDates)
    selection_process: List[str] = Field(default_factory=list)
    exam_patterns: List[ExamPatternUnit] = Field(default_factory=list)
    syllabus_summary: Optional[str] = None
    application_mode: str = "Online"
    official_apply_url: Optional[str] = None
    official_notification_url: Optional[str] = None
    official_source_domain: Optional[str] = None
    posting_location: str = "All India"
    state_code: str = "ALL"
    documents_required: List[str] = Field(default_factory=list)
    raw_evidence: List[FactEvidence] = Field(default_factory=list)
    confidence_score: float = Field(default=95.0, ge=0.0, le=100.0)
    is_corrigendum: bool = False
    corrigendum_details: Optional[str] = None
