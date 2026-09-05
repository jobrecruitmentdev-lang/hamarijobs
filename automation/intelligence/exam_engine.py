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
    Manages Master Exam Entities, Exam Phases, Patterns, Detailed Syllabus Units,
    Historical Cutoff Records, and Preparation Analytics across All Major Government Sectors.
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
            # 1. SSC CGL
            {
                "name": "SSC Combined Graduate Level (CGL) Examination",
                "short_name": "SSC CGL",
                "slug": "ssc-cgl",
                "conducting_body": "Staff Selection Commission (SSC)",
                "category": "Staff Selection",
                "frequency": "Annual",
                "overview": "SSC CGL is India's premier national recruitment examination conducted to recruit Group 'B' and Group 'C' Officers across Ministries, Departments, and Organizations of the Government of India.",
                "eligibility_summary": "Bachelor's Degree in any discipline from a recognized University.",
                "age_limit_summary": "18 to 32 years with statutory relaxations for reserved categories.",
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
                    {"year": 2025, "category": "ST", "cutoff": 118.12, "total": 200.0}
                ]
            },

            # 2. UPSC CSE
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
                    {"year": 2025, "category": "UR", "cutoff": 75.41, "total": 200.0},
                    {"year": 2025, "category": "OBC", "cutoff": 74.75, "total": 200.0},
                    {"year": 2025, "category": "EWS", "cutoff": 68.02, "total": 200.0},
                    {"year": 2025, "category": "SC", "cutoff": 59.25, "total": 200.0},
                    {"year": 2025, "category": "ST", "cutoff": 47.82, "total": 200.0}
                ]
            },

            # 3. RRB NTPC
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
                    {"year": 2025, "category": "UR", "cutoff": 74.20, "total": 100.0},
                    {"year": 2025, "category": "OBC", "cutoff": 69.80, "total": 100.0},
                    {"year": 2025, "category": "EWS", "cutoff": 66.50, "total": 100.0},
                    {"year": 2025, "category": "SC", "cutoff": 62.40, "total": 100.0},
                    {"year": 2025, "category": "ST", "cutoff": 55.10, "total": 100.0}
                ]
            },

            # 4. IBPS PO
            {
                "name": "IBPS Probationary Officer (PO) CRP",
                "short_name": "IBPS PO",
                "slug": "ibps-po",
                "conducting_body": "Institute of Banking Personnel Selection",
                "category": "Banking",
                "frequency": "Annual",
                "overview": "IBPS PO recruits Management Trainees/Probationary Officers across 11 Public Sector Participating Banks across India.",
                "eligibility_summary": "Graduation Degree in any discipline from a recognized University.",
                "age_limit_summary": "20 to 30 years with statutory relaxations.",
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
            },

            # 5. SBI PO
            {
                "name": "State Bank of India Probationary Officer (PO)",
                "short_name": "SBI PO",
                "slug": "sbi-po",
                "conducting_body": "State Bank of India (SBI)",
                "category": "Banking",
                "frequency": "Annual",
                "overview": "SBI PO is the most sought-after banking officer recruitment in India for appointments to managerial cadre in State Bank of India.",
                "eligibility_summary": "Bachelor's Degree in any discipline from a recognized University.",
                "age_limit_summary": "21 to 30 years with statutory relaxations.",
                "selection_stages_summary": "Phase I (Prelims CBT) -> Phase II (Mains & Descriptive) -> Phase III (Psychometric, GE & Interview).",
                "preparation_strategy": "Practice advanced level Data Interpretation, logical reasoning puzzles, and English comprehension with strict sectional timing.",
                "phases": [
                    {
                        "phase_name": "Phase I Preliminary Exam",
                        "phase_order": 1,
                        "mode": "Online (CBT)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "English Language", "q": 30, "m": 30, "dur": 20, "neg": "0.25 marks"},
                            {"subject": "Quantitative Aptitude", "q": 35, "m": 35, "dur": 20, "neg": "0.25 marks"},
                            {"subject": "Reasoning Ability", "q": 35, "m": 35, "dur": 20, "neg": "0.25 marks"}
                        ],
                        "syllabus": [
                            {"subject": "Quantitative Aptitude", "topic": "Advanced Data Interpretation", "weightage": 50.0, "subtopics": ["Radar Graph", "Missing DI", "Caselets"]},
                            {"subject": "Reasoning Ability", "topic": "High-Level Puzzles", "weightage": 50.0, "subtopics": ["Uncertain Puzzles", "Input-Output", "Critical Reasoning"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "UR", "cutoff": 59.25, "total": 100.0},
                    {"year": 2025, "category": "OBC", "cutoff": 58.00, "total": 100.0},
                    {"year": 2025, "category": "EWS", "cutoff": 58.00, "total": 100.0},
                    {"year": 2025, "category": "SC", "cutoff": 53.50, "total": 100.0},
                    {"year": 2025, "category": "ST", "cutoff": 47.75, "total": 100.0}
                ]
            },

            # 6. SBI Clerk
            {
                "name": "SBI Junior Associates (Customer Support & Sales)",
                "short_name": "SBI Clerk",
                "slug": "sbi-clerk",
                "conducting_body": "State Bank of India (SBI)",
                "category": "Banking",
                "frequency": "Annual",
                "overview": "Recruitment of Junior Associates in clerical cadre in State Bank of India branches across India.",
                "eligibility_summary": "Graduation in any discipline from a recognized University.",
                "age_limit_summary": "20 to 28 years.",
                "selection_stages_summary": "Phase I (Prelims Exam) -> Phase II (Mains Exam) -> Local Language Test.",
                "preparation_strategy": "Speed and accuracy are decisive factors. Practice 50+ mock tests to maximize Prelims attempt rate.",
                "phases": [
                    {
                        "phase_name": "Preliminary Examination",
                        "phase_order": 1,
                        "mode": "Online (CBT)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "English Language", "q": 30, "m": 30, "dur": 20, "neg": "0.25 marks"},
                            {"subject": "Numerical Ability", "q": 35, "m": 35, "dur": 20, "neg": "0.25 marks"},
                            {"subject": "Reasoning Ability", "q": 35, "m": 35, "dur": 20, "neg": "0.25 marks"}
                        ],
                        "syllabus": [
                            {"subject": "Numerical Ability", "topic": "Simplification & Approximation", "weightage": 35.0, "subtopics": ["BODMAS", "Square Roots", "Percentages"]},
                            {"subject": "Reasoning Ability", "topic": "Syllogisms & Inequalities", "weightage": 30.0, "subtopics": ["Only a few Cases", "Coded Inequality"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "UR", "cutoff": 71.50, "total": 100.0},
                    {"year": 2025, "category": "OBC", "cutoff": 68.75, "total": 100.0},
                    {"year": 2025, "category": "EWS", "cutoff": 69.00, "total": 100.0},
                    {"year": 2025, "category": "SC", "cutoff": 61.25, "total": 100.0},
                    {"year": 2025, "category": "ST", "cutoff": 53.50, "total": 100.0}
                ]
            },

            # 7. SSC CHSL
            {
                "name": "SSC Combined Higher Secondary Level (10+2) Examination",
                "short_name": "SSC CHSL",
                "slug": "ssc-chsl",
                "conducting_body": "Staff Selection Commission (SSC)",
                "category": "Staff Selection",
                "frequency": "Annual",
                "overview": "SSC CHSL recruits Lower Division Clerks (LDC), Junior Secretariat Assistants (JSA), and Data Entry Operators (DEO) across Central Government ministries.",
                "eligibility_summary": "12th Standard or equivalent examination passed from a recognized Board.",
                "age_limit_summary": "18 to 27 years with standard reservations.",
                "selection_stages_summary": "Tier 1 (Computer Based Examination) -> Tier 2 (CBT & Typing/Skill Test).",
                "preparation_strategy": "Strengthen high-accuracy arithmetic math and speed typing skills for the mandatory Tier 2 skill test.",
                "phases": [
                    {
                        "phase_name": "Tier 1 Examination",
                        "phase_order": 1,
                        "mode": "Online (CBT)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "English Language", "q": 25, "m": 50, "dur": 60, "neg": "0.50 marks"},
                            {"subject": "General Intelligence", "q": 25, "m": 50, "dur": 60, "neg": "0.50 marks"},
                            {"subject": "Quantitative Aptitude", "q": 25, "m": 50, "dur": 60, "neg": "0.50 marks"},
                            {"subject": "General Awareness", "q": 25, "m": 50, "dur": 60, "neg": "0.50 marks"}
                        ],
                        "syllabus": [
                            {"subject": "English", "topic": "Grammar & Vocab", "weightage": 25.0, "subtopics": ["Spotting Error", "Direct Indirect", "Active Passive"]},
                            {"subject": "Quant", "topic": "Basic Arithmetic", "weightage": 25.0, "subtopics": ["Profit Loss", "Time Work", "Algebra"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "UR", "cutoff": 153.25, "total": 200.0},
                    {"year": 2025, "category": "OBC", "cutoff": 152.00, "total": 200.0},
                    {"year": 2025, "category": "EWS", "cutoff": 150.50, "total": 200.0},
                    {"year": 2025, "category": "SC", "cutoff": 136.40, "total": 200.0},
                    {"year": 2025, "category": "ST", "cutoff": 124.80, "total": 200.0}
                ]
            },

            # 8. UPSC CDS
            {
                "name": "Combined Defence Services Examination (CDS)",
                "short_name": "UPSC CDS",
                "slug": "upsc-cds",
                "conducting_body": "Union Public Service Commission (UPSC)",
                "category": "Defence",
                "frequency": "Biannual",
                "overview": "UPSC CDS is conducted twice a year for admission to Indian Military Academy (IMA), Indian Naval Academy (INA), Air Force Academy (AFA), and Officers Training Academy (OTA).",
                "eligibility_summary": "Degree of a recognized University (Engineering Degree required for INA/AFA).",
                "age_limit_summary": "19 to 24 years (IMA/INA/AFA), 19 to 25 years (OTA).",
                "selection_stages_summary": "Written Examination (Offline) -> SSB Interview (5-Day Testing) -> Document Verification & Medical Examination.",
                "preparation_strategy": "Maintain consistent daily physical fitness alongside conceptual mastery of Elementary Mathematics and General Knowledge.",
                "phases": [
                    {
                        "phase_name": "Written Examination",
                        "phase_order": 1,
                        "mode": "Offline (Pen & Paper)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "English", "q": 120, "m": 100, "dur": 120, "neg": "0.33 marks"},
                            {"subject": "General Knowledge", "q": 120, "m": 100, "dur": 120, "neg": "0.33 marks"},
                            {"subject": "Elementary Mathematics", "q": 100, "m": 100, "dur": 120, "neg": "0.33 marks"}
                        ],
                        "syllabus": [
                            {"subject": "GK", "topic": "Defence & Current Events", "weightage": 35.0, "subtopics": ["Indian Armed Forces", "Modern Warfare", "Geography"]},
                            {"subject": "Math", "topic": "Geometry & Trigonometry", "weightage": 35.0, "subtopics": ["Circles", "Triangles", "Heights & Distances"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "IMA", "cutoff": 136.00, "total": 300.0},
                    {"year": 2025, "category": "INA", "cutoff": 128.00, "total": 300.0},
                    {"year": 2025, "category": "AFA", "cutoff": 148.00, "total": 300.0},
                    {"year": 2025, "category": "OTA (Men)", "cutoff": 102.00, "total": 200.0},
                    {"year": 2025, "category": "OTA (Women)", "cutoff": 102.00, "total": 200.0}
                ]
            },

            # 9. UPSC NDA
            {
                "name": "National Defence Academy & Naval Academy Examination",
                "short_name": "UPSC NDA",
                "slug": "upsc-nda",
                "conducting_body": "Union Public Service Commission (UPSC)",
                "category": "Defence",
                "frequency": "Biannual",
                "overview": "UPSC NDA is the gateway for 10+2 candidates to join the Army, Navy, and Air Force wings of the NDA and 10+2 Cadet Entry Scheme of INA.",
                "eligibility_summary": "12th Class pass of 10+2 pattern (Physics & Math required for Air Force & Navy).",
                "age_limit_summary": "16.5 to 19.5 years.",
                "selection_stages_summary": "Written Exam (900 Marks) -> SSB Interview (900 Marks) -> Medical Examination.",
                "preparation_strategy": "Master 11th and 12th standard mathematics syllabus and build general scientific curiosity.",
                "phases": [
                    {
                        "phase_name": "Written Examination",
                        "phase_order": 1,
                        "mode": "Offline (OMR)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "Mathematics", "q": 120, "m": 300, "dur": 150, "neg": "0.83 marks"},
                            {"subject": "General Ability Test (GAT)", "q": 150, "m": 600, "dur": 150, "neg": "1.33 marks"}
                        ],
                        "syllabus": [
                            {"subject": "Math", "topic": "Calculus & Algebra", "weightage": 40.0, "subtopics": ["Matrices", "Differential Calculus", "Vectors"]},
                            {"subject": "GAT", "topic": "English & General Science", "weightage": 60.0, "subtopics": ["Physics", "Chemistry", "World History"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "Written Cutoff", "cutoff": 355.00, "total": 900.0},
                    {"year": 2025, "category": "Final Recommended", "cutoff": 715.00, "total": 1800.0}
                ]
            },

            # 10. IAF AFCAT
            {
                "name": "Air Force Common Admission Test (AFCAT)",
                "short_name": "IAF AFCAT",
                "slug": "iaf-afcat",
                "conducting_body": "Indian Air Force (CDAC)",
                "category": "Defence",
                "frequency": "Biannual",
                "overview": "AFCAT recruits Group 'A' Gazetted Officers in Flying and Ground Duty (Technical & Non-Technical) branches of the Indian Air Force.",
                "eligibility_summary": "Graduate Degree with min 60% marks and 50% in Math & Physics at 10+2.",
                "age_limit_summary": "20 to 24 years (Flying), 20 to 26 years (Ground Duty).",
                "selection_stages_summary": "Online CBT Exam -> AFSB Testing (5-Day Interview & Psychological Testing) -> Medical Board.",
                "preparation_strategy": "Practice spatial ability and military aptitude reasoning along with high-speed verbal ability.",
                "phases": [
                    {
                        "phase_name": "AFCAT Online CBT",
                        "phase_order": 1,
                        "mode": "Online (CBT)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "General Awareness, Verbal Ability, Numerical Ability, Reasoning & Military Aptitude", "q": 100, "m": 300, "dur": 120, "neg": "1.00 mark"}
                        ],
                        "syllabus": [
                            {"subject": "Military Aptitude", "topic": "Spatial & Embedded Figures", "weightage": 25.0, "subtopics": ["Figure Matrix", "Pattern Completion"]},
                            {"subject": "General Awareness", "topic": "Defence & International Affairs", "weightage": 25.0, "subtopics": ["Air Force Commands", "Aircraft & Missiles"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "AFCAT Cutoff", "cutoff": 155.00, "total": 300.0},
                    {"year": 2024, "category": "AFCAT Cutoff", "cutoff": 151.00, "total": 300.0}
                ]
            },

            # 11. RPF SI & Constable
            {
                "name": "Railway Protection Force (RPF) SI & Constable",
                "short_name": "RPF SI & Constable",
                "slug": "rpf-si-constable",
                "conducting_body": "Railway Protection Force / Ministry of Railways",
                "category": "Railways",
                "frequency": "Biennial",
                "overview": "Recruitment of Sub-Inspectors (Executive) and Constables in Railway Protection Force across Indian Railway divisions.",
                "eligibility_summary": "Graduation for Sub-Inspector / 10th Pass for Constable.",
                "age_limit_summary": "18 to 28 years for Constable, 20 to 28 years for Sub-Inspector.",
                "selection_stages_summary": "CBT Exam -> Physical Efficiency Test (PET) & Physical Measurement Test (PMT) -> Document Verification.",
                "preparation_strategy": "Focus on high-speed mental arithmetic and daily physical endurance (1600m run, long jump, high jump).",
                "phases": [
                    {
                        "phase_name": "Computer Based Test (CBT)",
                        "phase_order": 1,
                        "mode": "Online (CBT)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "General Awareness", "q": 50, "m": 50, "dur": 90, "neg": "0.33 marks"},
                            {"subject": "Arithmetic", "q": 35, "m": 35, "dur": 90, "neg": "0.33 marks"},
                            {"subject": "General Intelligence & Reasoning", "q": 35, "m": 35, "dur": 90, "neg": "0.33 marks"}
                        ],
                        "syllabus": [
                            {"subject": "Arithmetic", "topic": "Percentages & Ratio", "weightage": 35.0, "subtopics": ["Time Distance", "Average", "Simple Compound Interest"]},
                            {"subject": "General Awareness", "topic": "Indian Railways & Constitution", "weightage": 50.0, "subtopics": ["Railway Zones", "History", "Current Affairs"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "UR (SI)", "cutoff": 94.50, "total": 120.0},
                    {"year": 2025, "category": "OBC (SI)", "cutoff": 92.00, "total": 120.0},
                    {"year": 2025, "category": "SC (SI)", "cutoff": 84.25, "total": 120.0},
                    {"year": 2025, "category": "ST (SI)", "cutoff": 81.50, "total": 120.0}
                ]
            },

            # 12. GPSC Class 1 & 2
            {
                "name": "Gujarat Administrative Service (GAS) Class 1 & 2",
                "short_name": "GPSC Class 1-2",
                "slug": "gpsc-class-1-2",
                "conducting_body": "Gujarat Public Service Commission (GPSC)",
                "category": "State PSC",
                "frequency": "Annual",
                "overview": "Gujarat Civil Services Examination for appointment to Deputy Collector, DySP, District Registrar, and Taluka Development Officer.",
                "eligibility_summary": "Bachelor's Degree in any faculty from a recognized University.",
                "age_limit_summary": "20 to 36 years with Gujarat state statutory relaxations.",
                "selection_stages_summary": "Preliminary Examination (Objective) -> Main Examination (Descriptive) -> Personal Interview.",
                "preparation_strategy": "In-depth study of Gujarat's History, Cultural Heritage, Geography, and State Government development schemes.",
                "phases": [
                    {
                        "phase_name": "Preliminary Examination",
                        "phase_order": 1,
                        "mode": "Offline (OMR)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "General Studies I", "q": 200, "m": 200, "dur": 180, "neg": "0.30 marks"},
                            {"subject": "General Studies II", "q": 200, "m": 200, "dur": 180, "neg": "0.30 marks"}
                        ],
                        "syllabus": [
                            {"subject": "GS I", "topic": "History & Heritage of Gujarat", "weightage": 30.0, "subtopics": ["Solanki Era", "Indus Valley in Gujarat", "Folklore"]},
                            {"subject": "GS II", "topic": "Economy of Gujarat & Public Administration", "weightage": 35.0, "subtopics": ["Ports & Logistics", "Sardar Sarovar", "Panchayati Raj"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "General (Male)", "cutoff": 139.50, "total": 400.0},
                    {"year": 2025, "category": "General (Female)", "cutoff": 128.00, "total": 400.0},
                    {"year": 2025, "category": "EWS (Male)", "cutoff": 139.50, "total": 400.0},
                    {"year": 2025, "category": "SEBC (Male)", "cutoff": 139.50, "total": 400.0}
                ]
            },

            # 13. UPPSC PCS
            {
                "name": "UPPSC Combined State / Upper Subordinate Services (PCS)",
                "short_name": "UPPSC PCS",
                "slug": "uppsc-pcs",
                "conducting_body": "Uttar Pradesh Public Service Commission",
                "category": "State PSC",
                "frequency": "Annual",
                "overview": "Uttar Pradesh PCS exam for recruitment to Sub-Divisional Magistrate (SDM), Deputy Superintendent of Police (DSP), Block Development Officer (BDO), and ARTO.",
                "eligibility_summary": "Bachelor's Degree from any recognized University.",
                "age_limit_summary": "21 to 40 years with UP domicile relaxations.",
                "selection_stages_summary": "Preliminary Exam (Paper 1 & Paper 2 CSAT) -> Mains Exam (Descriptive) -> Interview.",
                "preparation_strategy": "Master UP Specific General Studies (UP Special Papers 5 & 6) and current socio-economic initiatives.",
                "phases": [
                    {
                        "phase_name": "Preliminary Examination",
                        "phase_order": 1,
                        "mode": "Offline (OMR)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "General Studies I", "q": 150, "m": 200, "dur": 120, "neg": "0.33 marks"},
                            {"subject": "General Studies II (CSAT)", "q": 100, "m": 200, "dur": 120, "neg": "0.33 marks (33% Qualifying)"}
                        ],
                        "syllabus": [
                            {"subject": "GS I", "topic": "Uttar Pradesh Special & National History", "weightage": 30.0, "subtopics": ["UP History & Culture", "Indian Polity", "Geography"]},
                            {"subject": "GS I", "topic": "Current Affairs & Environment", "weightage": 25.0, "subtopics": ["National Schemes", "Ecology"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "UR", "cutoff": 125.00, "total": 200.0},
                    {"year": 2025, "category": "OBC", "cutoff": 124.00, "total": 200.0},
                    {"year": 2025, "category": "EWS", "cutoff": 123.00, "total": 200.0},
                    {"year": 2025, "category": "SC", "cutoff": 112.00, "total": 200.0},
                    {"year": 2025, "category": "ST", "cutoff": 105.00, "total": 200.0}
                ]
            },

            # 14. BPSC CCE
            {
                "name": "Bihar Combined Competitive Examination (CCE)",
                "short_name": "BPSC CCE",
                "slug": "bpsc-cce",
                "conducting_body": "Bihar Public Service Commission (BPSC)",
                "category": "State PSC",
                "frequency": "Annual",
                "overview": "BPSC CCE recruits administrative cadre officers including Sub-Divisional Officers, Bihar Police Service (DSP), Commercial Tax Officers, and Revenue Officers.",
                "eligibility_summary": "Graduation Degree from a recognized University.",
                "age_limit_summary": "20/21/22 to 37 years (General Male), 40 years (General Female/BC/EBC).",
                "selection_stages_summary": "Preliminary Examination (Objective with Negative Marking) -> Mains Examination -> Interview.",
                "preparation_strategy": "Give special emphasis to Bihar History (Champaran, 1857 Revolt in Bihar, Kunwar Singh), Bihar Geography, and Budget.",
                "phases": [
                    {
                        "phase_name": "Preliminary Examination",
                        "phase_order": 1,
                        "mode": "Offline (OMR)",
                        "is_qualifying": True,
                        "patterns": [
                            {"subject": "General Studies (Objective)", "q": 150, "m": 150, "dur": 120, "neg": "0.33 marks"}
                        ],
                        "syllabus": [
                            {"subject": "GS", "topic": "Bihar Special & Freedom Movement", "weightage": 30.0, "subtopics": ["Kunwar Singh Role", "Ancient Magadha", "Modern Bihar"]},
                            {"subject": "GS", "topic": "General Science & Current Affairs", "weightage": 40.0, "subtopics": ["Physics", "Chemistry", "Biology", "National Events"]}
                        ]
                    }
                ],
                "cutoffs": [
                    {"year": 2025, "category": "UR", "cutoff": 91.00, "total": 150.0},
                    {"year": 2025, "category": "EWS", "cutoff": 87.00, "total": 150.0},
                    {"year": 2025, "category": "BC", "cutoff": 88.00, "total": 150.0},
                    {"year": 2025, "category": "EBC", "cutoff": 86.00, "total": 150.0},
                    {"year": 2025, "category": "SC", "cutoff": 79.00, "total": 150.0},
                    {"year": 2025, "category": "ST", "cutoff": 74.00, "total": 150.0}
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
                # Update details if needed
                cur.execute("""
                    UPDATE exams SET 
                        name = %s, short_name = %s, conducting_body = %s, category = %s,
                        frequency = %s, overview = %s, eligibility_summary = %s, age_limit_summary = %s,
                        selection_stages_summary = %s, preparation_strategy = %s, is_active = 1
                    WHERE id = %s;
                """, (
                    e["name"], e["short_name"], e["conducting_body"], e["category"],
                    e["frequency"], e["overview"], e["eligibility_summary"], e["age_limit_summary"],
                    e["selection_stages_summary"], e["preparation_strategy"], exam_id
                ))

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
                else:
                    cur.execute("""
                        UPDATE cutoff_records SET cutoff_marks = %s, total_marks = %s WHERE exam_id = %s AND year = %s AND category = %s;
                    """, (cut["cutoff"], cut["total"], exam_id, cut["year"], cut["category"]))

        conn.close()
        logger.info("✨ [ExamIntelligence] All 14 Master Exam Hubs seeded and synchronized successfully!")

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
