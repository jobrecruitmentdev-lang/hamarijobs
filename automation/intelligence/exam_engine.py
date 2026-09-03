import sys
import uuid
import json
from pathlib import Path
from typing import Dict, Any, List, Optional
import pymysql

ROOT_DIR = str(Path(__file__).resolve().parent.parent.parent)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from automation.config import settings
from automation.logger import logger

class ExamIntelligenceEngine:
    """
    Manages Exam Entities, Phases, Exam Patterns, Detailed Syllabus Units,
    Historical Cutoff Records, and Previous Year Question (PYQ) Analytics.
    """
    
    def __init__(self):
        pass
        
    def get_db_connection(self):
        return pymysql.connect(
            host=settings.MYSQL_HOST,
            user=settings.MYSQL_USER,
            password=settings.MYSQL_PASSWORD,
            database=settings.MYSQL_DB,
            cursorclass=pymysql.cursors.DictCursor,
            autocommit=True
        )

    def seed_master_exam_hubs(self):
        """
        Seeds foundational master records for Tier 1 National & State Exams
        including their phases, patterns, syllabus topics, and cutoff trends.
        """
        conn = self.get_db_connection()
        cur = conn.cursor()

        exams_data = [
            {
                "name": "SSC Combined Graduate Level (CGL) Examination",
                "short_name": "SSC CGL",
                "slug": "ssc-cgl",
                "conducting_body": "Staff Selection Commission (SSC)",
                "category": "Staff Selection",
                "frequency": "Annual",
                "overview": "SSC CGL is one of India's most prestigious national recruitment examinations conducted to recruit Group 'B' and Group 'C' Officers across Ministries, Departments, and Organizations of the Government of India.",
                "eligibility_summary": "Bachelor's Degree in any discipline from a recognized University.",
                "age_limit_summary": "18 to 32 years (depending on the post) with statutory age relaxations for reserved categories.",
                "selection_stages_summary": "Tier 1 (Computer Based Test) -> Tier 2 (Computer Based Test & Data Entry Skill Test) -> Document Verification.",
                "preparation_strategy": "Master previous year question patterns, prioritize high-weightage topics in Quantitative Aptitude and English Comprehension, and practice daily mock tests.",
                "phases": [
                    {
                        "phase_name": "Tier 1 (Computer Based Exam)",
                        "phase_order": 1,
                        "mode": "Online (CBT)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "General Intelligence & Reasoning", "q": 25, "m": 50, "dur": 60, "neg": "0.50 marks"},
                            {"subject": "General Awareness", "q": 25, "m": 50, "dur": 60, "neg": "0.50 marks"},
                            {"subject": "Quantitative Aptitude", "q": 25, "m": 50, "dur": 60, "neg": "0.50 marks"},
                            {"subject": "English Comprehension", "q": 25, "m": 50, "dur": 60, "neg": "0.50 marks"},
                        ],
                        "syllabus": [
                            {"subject": "Reasoning", "topic": "Analogies & Classification", "weightage": 16.0, "subtopics": ["Semantic Analogy", "Symbolic/Number Analogy", "Figural Analogy"]},
                            {"subject": "Reasoning", "topic": "Coding-Decoding & Series", "weightage": 20.0, "subtopics": ["Number Series", "Letter Series", "Matrix Coding"]},
                            {"subject": "Quantitative Aptitude", "topic": "Arithmetic & Number Systems", "weightage": 35.0, "subtopics": ["Percentages", "Profit & Loss", "Ratio & Proportion", "Time & Work", "SI & CI"]},
                            {"subject": "Quantitative Aptitude", "topic": "Advanced Mathematics", "weightage": 40.0, "subtopics": ["Algebra", "Geometry & Mensuration", "Trigonometry", "Heights & Distances"]},
                            {"subject": "General Awareness", "topic": "Static GK & Polity", "weightage": 30.0, "subtopics": ["Indian Constitution", "History & Culture", "Geography", "Economics"]},
                            {"subject": "General Awareness", "topic": "Current Affairs & Science", "weightage": 40.0, "subtopics": ["National Events", "Awards & Honours", "General Science"]},
                            {"subject": "English", "topic": "Grammar & Vocabulary", "weightage": 50.0, "subtopics": ["Spotting Errors", "Fill in the Blanks", "Synonyms & Antonyms", "Idioms & Phrases"]},
                            {"subject": "English", "topic": "Reading Comprehension", "weightage": 30.0, "subtopics": ["Cloze Test", "Passage Comprehension"]},
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "UR", "cutoff": 150.04, "total": 200.0},
                    {"year": 2025, "category": "OBC", "cutoff": 145.32, "total": 200.0},
                    {"year": 2025, "category": "EWS", "cutoff": 143.10, "total": 200.0},
                    {"year": 2025, "category": "SC", "cutoff": 126.86, "total": 200.0},
                    {"year": 2025, "category": "ST", "cutoff": 118.12, "total": 200.0},
                    {"year": 2024, "category": "UR", "cutoff": 150.04, "total": 200.0},
                    {"year": 2024, "category": "OBC", "cutoff": 145.34, "total": 200.0}
                ]
            },
            {
                "name": "UPSC Civil Services Examination (CSE)",
                "short_name": "UPSC CSE",
                "slug": "upsc-cse",
                "conducting_body": "Union Public Service Commission (UPSC)",
                "category": "Civil Services",
                "frequency": "Annual",
                "overview": "UPSC Civil Services Examination is India's premier administrative recruitment exam for IAS, IPS, IFS, IRS, and Central Group 'A' Services.",
                "eligibility_summary": "Graduate Degree in any discipline from a recognized University.",
                "age_limit_summary": "21 to 32 years with relaxations for reserved categories.",
                "selection_stages_summary": "Preliminary Examination (Objective) -> Main Examination (Written Descriptive) -> Personality Test (Interview).",
                "preparation_strategy": "Integrate Prelims and Mains preparation, maintain consistent current affairs tracking from standard national dailies, and practice answer writing.",
                "phases": [
                    {
                        "phase_name": "Preliminary Examination",
                        "phase_order": 1,
                        "mode": "Offline (OMR)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "General Studies Paper I", "q": 100, "m": 200, "dur": 120, "neg": "0.66 marks"},
                            {"subject": "CSAT Paper II", "q": 80, "m": 200, "dur": 120, "neg": "0.83 marks (Qualifying 33%)"}
                        ],
                        "syllabus": [
                            {"subject": "GS Paper 1", "topic": "Indian Polity & Governance", "weightage": 22.0, "subtopics": ["Constitution", "Panchayati Raj", "Public Policy", "Rights Issues"]},
                            {"subject": "GS Paper 1", "topic": "Economy & Social Development", "weightage": 20.0, "subtopics": ["Sustainable Development", "Poverty", "Inclusion", "Demographics"]},
                            {"subject": "GS Paper 1", "topic": "Environment & Ecology", "weightage": 25.0, "subtopics": ["Biodiversity", "Climate Change", "Environmental Impact"]},
                            {"subject": "GS Paper 1", "topic": "History of India & National Movement", "weightage": 18.0, "subtopics": ["Ancient & Medieval India", "Modern Indian History", "Freedom Struggle"]},
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2024, "category": "UR", "cutoff": 75.41, "total": 200.0},
                    {"year": 2024, "category": "OBC", "cutoff": 74.75, "total": 200.0},
                    {"year": 2024, "category": "EWS", "cutoff": 68.02, "total": 200.0},
                    {"year": 2024, "category": "SC", "cutoff": 59.25, "total": 200.0},
                    {"year": 2024, "category": "ST", "cutoff": 47.82, "total": 200.0}
                ]
            },
            {
                "name": "RRB Non-Technical Popular Categories (NTPC)",
                "short_name": "RRB NTPC",
                "slug": "rrb-ntpc",
                "conducting_body": "Railway Recruitment Control Board",
                "category": "Railways",
                "frequency": "Biennial",
                "overview": "RRB NTPC recruits for posts like Station Master, Goods Guard, Senior Clerk cum Typist, Junior Accounts Assistant, and Commercial Apprentice in Indian Railways.",
                "eligibility_summary": "12th Pass for Under Graduate Posts / Bachelor's Degree for Graduate Posts.",
                "age_limit_summary": "18 to 33 years for 12th level posts, 18 to 36 years for Graduate level posts.",
                "selection_stages_summary": "CBT 1 (Screening) -> CBT 2 (Scoring) -> Typing Skill Test / CBAT -> Document Verification & Medical.",
                "preparation_strategy": "Focus on high-speed mental math, General Science, and General Awareness of Indian Railways.",
                "phases": [
                    {
                        "phase_name": "CBT 1 Examination",
                        "phase_order": 1,
                        "mode": "Online (CBT)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "General Awareness", "q": 40, "m": 40, "dur": 90, "neg": "0.33 marks"},
                            {"subject": "Mathematics", "q": 30, "m": 30, "dur": 90, "neg": "0.33 marks"},
                            {"subject": "General Intelligence & Reasoning", "q": 30, "m": 30, "dur": 90, "neg": "0.33 marks"}
                        ],
                        "syllabus": [
                            {"subject": "Mathematics", "topic": "Number System, BODMAS & Decimals", "weightage": 25.0, "subtopics": ["Fractions", "LCM & HCF", "Ratio", "Percentage"]},
                            {"subject": "General Awareness", "topic": "General Science & Current Events", "weightage": 45.0, "subtopics": ["Physics", "Chemistry", "Life Sciences up to 10th CBSE", "Current Affairs"]},
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2024, "category": "UR", "cutoff": 74.20, "total": 100.0},
                    {"year": 2024, "category": "OBC", "cutoff": 69.80, "total": 100.0},
                    {"year": 2024, "category": "SC", "cutoff": 62.40, "total": 100.0},
                    {"year": 2024, "category": "ST", "cutoff": 55.10, "total": 100.0}
                ]
            },
            {
                "name": "IBPS Probationary Officer (PO) CRP",
                "short_name": "IBPS PO",
                "slug": "ibps-po",
                "conducting_body": "Institute of Banking Personnel Selection",
                "category": "Banking",
                "frequency": "Annual",
                "overview": "IBPS PO recruits Management Trainees/Probationary Officers across 11 Public Sector Participating Banks across India.",
                "eligibility_summary": "Graduation Degree in any discipline from a recognized University.",
                "age_limit_summary": "20 to 30 years.",
                "selection_stages_summary": "Prelims Examination -> Mains Examination -> Common Interview -> Provisional Allotment.",
                "preparation_strategy": "Focus intensely on sectional timing, Data Interpretation, Puzzles/Seating Arrangement, and Banking Awareness.",
                "phases": [
                    {
                        "phase_name": "Preliminary Examination",
                        "phase_order": 1,
                        "mode": "Online (CBT)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "English Language", "q": 30, "m": 30, "dur": 20, "neg": "0.25 marks"},
                            {"subject": "Quantitative Aptitude", "q": 35, "m": 35, "dur": 20, "neg": "0.25 marks"},
                            {"subject": "Reasoning Ability", "q": 35, "m": 35, "dur": 20, "neg": "0.25 marks"}
                        ],
                        "syllabus": [
                            {"subject": "Quantitative Aptitude", "topic": "Data Interpretation & Quadratic Equations", "weightage": 45.0, "subtopics": ["Tabular DI", "Pie Chart", "Bar Graph", "Caselet DI"]},
                            {"subject": "Reasoning Ability", "topic": "Puzzles & Seating Arrangement", "weightage": 55.0, "subtopics": ["Linear Arrangement", "Circular Arrangement", "Floor Based Puzzles", "Box Puzzles"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "UR", "cutoff": 54.25, "total": 100.0},
                    {"year": 2025, "category": "OBC", "cutoff": 54.25, "total": 100.0},
                    {"year": 2025, "category": "EWS", "cutoff": 54.25, "total": 100.0},
                    {"year": 2025, "category": "SC", "cutoff": 48.00, "total": 100.0},
                    {"year": 2025, "category": "ST", "cutoff": 41.50, "total": 100.0}
                ]
            }
        ]

        for e in exams_data:
            exam_uuid = str(uuid.uuid4())
            cur.execute("SELECT id FROM exams WHERE slug = %s LIMIT 1;", (e["slug"],))
            existing = cur.fetchone()
            
            if not existing:
                cur.execute("""
                    INSERT INTO exams (exam_uuid, name, short_name, slug, conducting_body, category, frequency, overview, eligibility_summary, age_limit_summary, selection_stages_summary, preparation_strategy, is_active)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 1);
                """, (
                    exam_uuid, e["name"], e["short_name"], e["slug"], e["conducting_body"], e["category"],
                    e["frequency"], e["overview"], e["eligibility_summary"], e["age_limit_summary"],
                    e["selection_stages_summary"], e["preparation_strategy"]
                ))
                exam_id = cur.lastrowid
                logger.info(f"[ExamIntelligence] Created Exam Hub: {e['short_name']} (ID: {exam_id})")
            else:
                exam_id = existing["id"]

            # Phases, Patterns & Syllabus
            for phase in e.get("phases", []):
                cur.execute("SELECT id FROM exam_phases WHERE exam_id = %s AND phase_order = %s LIMIT 1;", (exam_id, phase["phase_order"]))
                p_exist = cur.fetchone()
                if not p_exist:
                    cur.execute("""
                        INSERT INTO exam_phases (exam_id, phase_name, phase_order, mode, is_qualifying)
                        VALUES (%s, %s, %s, %s, %s);
                    """, (exam_id, phase["phase_name"], phase["phase_order"], phase["mode"], phase["is_qualifying"]))
                    phase_id = cur.lastrowid
                else:
                    phase_id = p_exist["id"]

                # Patterns
                for pat in phase.get("patterns", []):
                    cur.execute("SELECT id FROM exam_patterns WHERE phase_id = %s AND subject_name = %s LIMIT 1;", (phase_id, pat["subject"]))
                    if not cur.fetchone():
                        cur.execute("""
                            INSERT INTO exam_patterns (exam_id, phase_id, subject_name, num_questions, max_marks, duration_minutes, negative_marking)
                            VALUES (%s, %s, %s, %s, %s, %s, %s);
                        """, (exam_id, phase_id, pat["subject"], pat["q"], pat["m"], pat["dur"], pat["neg"]))

                # Syllabus
                for syl in phase.get("syllabus", []):
                    cur.execute("SELECT id FROM exam_syllabus WHERE exam_id = %s AND phase_id = %s AND topic = %s LIMIT 1;", (exam_id, phase_id, syl["topic"]))
                    if not cur.fetchone():
                        cur.execute("""
                            INSERT INTO exam_syllabus (exam_id, phase_id, subject, topic, subtopics, weightage_percentage)
                            VALUES (%s, %s, %s, %s, %s, %s);
                        """, (exam_id, phase_id, syl["subject"], syl["topic"], json.dumps(syl.get("subtopics", [])), syl.get("weightage")))

            # Cutoffs
            for cut in e.get("cutoffs", []):
                cur.execute("SELECT id FROM cutoff_records WHERE exam_id = %s AND year = %s AND category = %s LIMIT 1;", (exam_id, cut["year"], cut["category"]))
                if not cur.fetchone():
                    cur.execute("""
                        INSERT INTO cutoff_records (exam_id, year, category, cutoff_marks, total_marks)
                        VALUES (%s, %s, %s, %s, %s);
                    """, (exam_id, cut["year"], cut["category"], cut["cutoff"], cut["total"]))

        conn.close()
        logger.info("✨ [ExamIntelligence] Master Exam Hubs seeded successfully!")

    def get_exam_analytics(self, slug: str) -> Optional[Dict[str, Any]]:
        """
        Retrieves comprehensive intelligence data for an exam by slug.
        """
        conn = self.get_db_connection()
        cur = conn.cursor()
        cur.execute("SELECT * FROM exams WHERE slug = %s LIMIT 1;", (slug,))
        exam = cur.fetchone()
        if not exam:
            conn.close()
            return None

        exam_id = exam["id"]
        
        # Fetch Phases
        cur.execute("SELECT * FROM exam_phases WHERE exam_id = %s ORDER BY phase_order ASC;", (exam_id,))
        phases = cur.fetchall()

        # Fetch Patterns
        cur.execute("SELECT * FROM exam_patterns WHERE exam_id = %s;", (exam_id,))
        patterns = cur.fetchall()

        # Fetch Syllabus
        cur.execute("SELECT * FROM exam_syllabus WHERE exam_id = %s ORDER BY weightage_percentage DESC;", (exam_id,))
        syllabus = cur.fetchall()

        # Fetch Cutoffs
        cur.execute("SELECT * FROM cutoff_records WHERE exam_id = %s ORDER BY year DESC, category ASC;", (exam_id,))
        cutoffs = cur.fetchall()

        # Fetch Related Active Recruitments
        cur.execute("SELECT * FROM recruitments WHERE organization_name LIKE %s OR title LIKE %s ORDER BY year DESC LIMIT 5;", (f"%{exam['short_name']}%", f"%{exam['short_name']}%"))
        recruitments = cur.fetchall()

        conn.close()
        return {
            "exam": exam,
            "phases": phases,
            "patterns": patterns,
            "syllabus": syllabus,
            "cutoffs": cutoffs,
            "recruitments": recruitments
        }

    def get_exam_hub_by_slug(self, slug: str) -> Optional[Dict[str, Any]]:
        return self.get_exam_analytics(slug)

if __name__ == "__main__":
    engine = ExamIntelligenceEngine()
    engine.seed_master_exam_hubs()
    analytics = engine.get_exam_analytics("ssc-cgl")
    print(f"\nExam Hub SSC CGL: {analytics['exam']['name']}")
    print(f"Total Syllabus Topics: {len(analytics['syllabus'])}")
    print(f"Total Cutoff Records: {len(analytics['cutoffs'])}")
