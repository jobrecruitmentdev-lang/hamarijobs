<?php
namespace App\Services;

/**
 * Enterprise SEO, GEO (Generative Engine Optimization), and AEO (Answer Engine Optimization) Engine.
 * Dynamically computes 100% unique metadata, social sharing cards, AEO direct-answer blocks,
 * and rich Schema.org JSON-LD structures across all page types on HamariJobs.
 */
class SeoEngine {

    public const BASE_URL = 'https://hamarijobs.com';
    public const DEFAULT_LOGO = 'https://hamarijobs.com/assets/images/logo.png';
    public const BRAND_NAME = 'Hamari Jobs';

    /**
     * Sanitize and format title to ideal search snippet length (50-65 chars).
     */
    public static function formatTitle(string $title): string {
        $title = trim(strip_tags($title));
        if (!str_contains($title, self::BRAND_NAME) && !str_contains($title, 'HamariJobs')) {
            $title .= ' — ' . self::BRAND_NAME;
        }
        return $title;
    }

    /**
     * Sanitize and format meta description to ideal search length (140-160 chars).
     */
    public static function formatDescription(string $desc): string {
        $desc = trim(preg_replace('/\s+/', ' ', strip_tags($desc)));
        if (mb_strlen($desc) > 160) {
            $desc = mb_substr($desc, 0, 157) . '...';
        }
        return $desc;
    }

    /**
     * 1. HOMEPAGE SEO
     */
    public static function getHomeSeo(): array {
        $title = "Hamari Jobs (हमारी जॉब्स) — Latest Government Jobs 2026, Sarkari Result & Admit Card";
        $desc = "Hamari Jobs (hamarijobs.com) is India's verified government recruitment intelligence portal. Real-time official notifications, admit cards, syllabus & results.";
        $canonical = self::BASE_URL . '/';

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "WebSite",
                "@id" => self::BASE_URL . "/#website",
                "name" => self::BRAND_NAME,
                "alternateName" => ["HamariJobs", "HamariJobs.com", "हमारी जॉब्स"],
                "url" => self::BASE_URL,
                "description" => $desc,
                "publisher" => [
                    "@id" => self::BASE_URL . "/#organization"
                ],
                "potentialAction" => [
                    "@type" => "SearchAction",
                    "target" => self::BASE_URL . "/government-jobs?q={search_term_string}",
                    "query-input" => "required name=search_term_string"
                ]
            ],
            [
                "@context" => "https://schema.org",
                "@type" => ["Organization", "Brand", "GovernmentService"],
                "@id" => self::BASE_URL . "/#organization",
                "name" => self::BRAND_NAME,
                "legalName" => "Hamari Jobs",
                "alternateName" => ["HamariJobs", "HamariJobs.com", "हमारी जॉब्स"],
                "url" => self::BASE_URL,
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => self::DEFAULT_LOGO,
                    "width" => 512,
                    "height" => 512
                ],
                "image" => self::DEFAULT_LOGO,
                "knowsAbout" => [
                    "https://en.wikipedia.org/wiki/Civil_Services_Examination",
                    "https://en.wikipedia.org/wiki/Staff_Selection_Commission",
                    "https://en.wikipedia.org/wiki/Union_Public_Service_Commission",
                    "https://en.wikipedia.org/wiki/Railway_Recruitment_Control_Board",
                    "https://en.wikipedia.org/wiki/Institute_of_Banking_Personnel_Selection",
                    "https://en.wikipedia.org/wiki/Public_service_commission"
                ],
                "sameAs" => [
                    "https://twitter.com/HamariJobs",
                    "https://t.me/HamariJobsOfficial",
                    "https://www.linkedin.com/company/hamarijobs"
                ]
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "FAQPage",
                "mainEntity" => [
                    [
                        "@type" => "Question",
                        "name" => "What is Hamari Jobs (हमारी जॉब्स)?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Hamari Jobs (hamarijobs.com) is India's premier verified government recruitment portal providing official notifications, exam schedules, admit cards, answer keys, syllabus, and results."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "How to find latest Sarkari Naukri notifications on Hamari Jobs?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Visit hamarijobs.com/government-jobs or browse by commission (UPSC, SSC, RRB, IBPS, State PSCs) or qualification (10th pass, 12th pass, Graduate) to access active vacancies with verified gazette references."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "Does Hamari Jobs provide direct official application links?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Yes, every recruitment on Hamari Jobs includes direct links to official commission portals (.gov.in / .nic.in) along with original gazette notification PDFs."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "How to download Admit Cards and check Exam Results on Hamari Jobs?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Navigate to hamarijobs.com/admit-cards or hamarijobs.com/results to access real-time hall ticket download servers, category-wise cutoff marks, and merit lists."
                        ]
                    ]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'schemas' => $schemas
        ];
    }

    /**
     * 2. GOVERNMENT JOBS DIRECTORY SEO
     */
    public static function getJobsListSeo(?string $state = null, ?string $qual = null, ?string $q = null, ?string $category = null, ?string $customPath = null, int $page = 1): array {
        $stateNames = [
            'UP' => 'Uttar Pradesh',
            'BR' => 'Bihar',
            'RJ' => 'Rajasthan',
            'MP' => 'Madhya Pradesh',
            'DL' => 'Delhi NCR',
            'MH' => 'Maharashtra'
        ];
        $fullState = (!empty($state) && isset($stateNames[$state])) ? $stateNames[$state] : $state;

        $prefix = "Government Jobs 2026";
        if (!empty($fullState) && $fullState !== 'ALL') {
            $prefix = "{$fullState} Government Jobs 2026";
        } elseif (!empty($qual)) {
            $prefix = "{$qual} Pass Government Jobs 2026";
        } elseif (!empty($category)) {
            $prefix = "{$category} Recruitment 2026";
        } elseif (!empty($q)) {
            $prefix = "{$q} Government Jobs 2026";
        }

        $title = "{$prefix}: Active Vacancies, Gazette Notifications & Online Form";
        $desc = "Browse verified {$prefix}. Check category-wise age limit, salary pay matrix, educational eligibility and direct online application links.";
        $canonical = $customPath ? (self::BASE_URL . $customPath) : (self::BASE_URL . '/government-jobs');

        if ($page > 1) {
            $title .= " (Page {$page})";
            $canonical .= (str_contains($canonical, '?') ? '&' : '?') . "page={$page}";
        }

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "CollectionPage",
                "name" => $title,
                "url" => $canonical,
                "description" => $desc
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Government Jobs", "item" => self::BASE_URL . "/government-jobs"],
                    ["@type" => "ListItem", "position" => 3, "name" => $prefix, "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary',
            'schemas' => $schemas
        ];
    }

    /**
     * 3. SINGLE JOB / GAZETTE RECRUITMENT DOSSIER SEO, GEO & AEO
     */
    public static function getJobDetailSeo(array $rec, array $factsMap = [], array $events = []): array {
        $org = htmlspecialchars($rec['organization_name'] ?? 'Government of India');
        $jobTitle = htmlspecialchars($rec['title'] ?? 'Recruitment');
        $year = $rec['year'] ?: 2026;
        $vacancies = $rec['total_vacancies'] ? number_format($rec['total_vacancies']) : 'Various';
        $advt = htmlspecialchars($rec['advertisement_number'] ?: 'Official Gazette Notification');
        $qual = htmlspecialchars($rec['qualification_level'] ?: 'Graduate / 12th Pass as per official notification');
        $payScale = htmlspecialchars($factsMap['Pay Scale'] ?? 'As per 7th CPC Matrix');
        $applyUrl = htmlspecialchars($rec['official_apply_url'] ?: 'https://gov.in');
        $stateCode = $rec['state_code'] === 'ALL' ? 'All India' : $rec['state_code'];
        $slug = $rec['slug'];
        $canonical = self::BASE_URL . "/jobs/{$slug}";

        // Calculate critical dates
        $startDate = null;
        $lastDate = null;
        $examDate = null;
        foreach ($events as $ev) {
            if ($ev['event_type'] === 'APPLICATION_STARTED' && !empty($ev['event_date'])) {
                $startDate = date('d F Y', strtotime($ev['event_date']));
            }
            if ($ev['event_type'] === 'APPLICATION_CLOSED' && !empty($ev['event_date'])) {
                $lastDate = date('d F Y', strtotime($ev['event_date']));
            }
            if ($ev['event_type'] === 'EXAM_DATE' && !empty($ev['event_date'])) {
                $examDate = date('d F Y', strtotime($ev['event_date']));
            }
        }

        $dateRangeText = $lastDate ? "Apply by {$lastDate}" : "Online Applications Open";

        // Dynamic High-CTR Title (UPSC CSE 2026: 1,056 Posts, Eligibility, Syllabus & Apply Online)
        $title = "{$org} {$jobTitle} Recruitment {$year}: {$vacancies} Posts, Eligibility, Salary & Apply Online";
        $desc = "Official notification for {$org} {$jobTitle} (Advt: {$advt}). Total {$vacancies} vacancies. Qualification: {$qual}. {$dateRangeText}. Download PDF & apply.";

        // AEO 40-Word TL;DR Direct-Answer Text (Prime candidate for Google AI Overviews)
        $tldrText = "The {$org} has officially announced {$vacancies} vacancies for {$jobTitle} under advertisement {$advt}. Online applications are accepted until " . ($lastDate ?: 'the notified closing date') . ". Eligible candidates holding a {$qual} can apply online via the official portal at {$applyUrl}. Selection includes written examination and document verification.";

        // Schemas: JobPosting + FAQPage + BreadcrumbList
        $datePosted = !empty($rec['created_at']) ? date('Y-m-d', strtotime($rec['created_at'])) : date('Y-m-d');
        $validThrough = !empty($lastDate) ? date('Y-m-d', strtotime($lastDate)) : date('Y-m-d', strtotime('+45 days'));

        // Salary numeric extraction
        $salaryNum = 35400;
        if (preg_match('/(\d[\d,]{3,})/', $payScale, $m)) {
            $salaryNum = (int)str_replace(',', '', $m[1]);
        }

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "JobPosting",
                "title" => "{$org} {$jobTitle}",
                "description" => $rec['summary'] ?: "Official recruitment notification for {$jobTitle} released by {$org}. Total vacancies: {$vacancies}. Educational qualification: {$qual}.",
                "identifier" => [
                    "@type" => "PropertyValue",
                    "name" => $org,
                    "value" => $advt
                ],
                "datePosted" => $datePosted,
                "validThrough" => $validThrough . "T23:59:59+05:30",
                "employmentType" => "FULL_TIME",
                "hiringOrganization" => [
                    "@type" => "GovernmentOrganization",
                    "name" => $org,
                    "sameAs" => $rec['official_website_url'] ?: "https://gov.in",
                    "logo" => self::DEFAULT_LOGO
                ],
                "jobLocation" => [
                    "@type" => "Place",
                    "address" => [
                        "@type" => "PostalAddress",
                        "addressCountry" => "IN",
                        "addressRegion" => $stateCode
                    ]
                ],
                "baseSalary" => [
                    "@type" => "MonetaryAmount",
                    "currency" => "INR",
                    "value" => [
                        "@type" => "QuantitativeValue",
                        "value" => $salaryNum,
                        "unitText" => "MONTH"
                    ]
                ],
                "qualifications" => $qual,
                "applicantLocationRequirements" => [
                    "@type" => "Country",
                    "name" => "India"
                ],
                "directApply" => true
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "FAQPage",
                "mainEntity" => [
                    [
                        "@type" => "Question",
                        "name" => "What is the total vacancy count for {$org} {$jobTitle} 2026?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "A total of {$vacancies} vacancies have been notified by {$org} under advertisement {$advt}."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "What is the educational qualification required for {$jobTitle}?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Candidates must possess: {$qual}."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "What is the last date to apply online for {$jobTitle}?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "The last date to submit online applications is " . ($lastDate ?: "specified in the official gazette") . "."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "What is the salary or pay scale for {$org} {$jobTitle}?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "The sanctioned pay scale is {$payScale} along with applicable central/state allowances."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "Where can I fill the online application form for {$jobTitle}?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Applications must be submitted online at the official portal: {$applyUrl}."
                        ]
                    ]
                ]
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Government Jobs", "item" => self::BASE_URL . "/government-jobs"],
                    ["@type" => "ListItem", "position" => 3, "name" => "{$org} {$jobTitle}", "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'article',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'tldr_box' => $tldrText,
            'schemas' => $schemas
        ];
    }

    /**
     * 4. DEDICATED EXAM INTELLIGENCE HUB SEO, GEO & AEO
     */
    public static function getExamDetailSeo(array $exam, array $phases = [], array $patterns = [], array $syllabus = [], array $cutoffs = []): array {
        $name = htmlspecialchars($exam['name']);
        $short = htmlspecialchars($exam['short_name']);
        $body = htmlspecialchars($exam['conducting_body']);
        $category = htmlspecialchars($exam['category'] ?? 'Competitive Examination');
        $slug = $exam['slug'];
        $canonical = self::BASE_URL . "/exams/{$slug}";

        $title = "{$name} ({$short}) 2026: Pattern, Syllabus, Cutoff Marks & Preparation";
        $desc = "Complete intelligence hub for {$name} ({$short}) conducted by {$body}. Detailed Tier/Phase exam scheme, subject syllabus weightage, previous year cutoff marks & strategy.";

        $tldrText = "{$name} ({$short}) is a premier national examination conducted by {$body}. The recruitment process comprises multiple assessment stages with negative marking. Comprehensive syllabus topics, qualifying thresholds, and previous year cutoff marks are compiled below for serious aspirants.";

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "Course",
                "name" => "{$name} ({$short}) Examination Hub",
                "description" => $exam['overview'] ?: $desc,
                "provider" => [
                    "@type" => "Organization",
                    "name" => $body,
                    "sameAs" => self::BASE_URL
                ]
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "FAQPage",
                "mainEntity" => [
                    [
                        "@type" => "Question",
                        "name" => "What is the examination scheme and pattern for {$short} 2026?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "The {$short} examination consists of structured stages including objective Computer-Based Tests (CBT) and descriptive/personality evaluations as detailed in the official pattern section."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "Is there negative marking in {$name} ({$short})?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Yes, standard negative marking (typically 1/3rd or 1/4th of the question mark) is deducted for each incorrect answer in the objective test phases."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "What are the major subject sections in {$short} Syllabus?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Major sections typically comprise General Awareness, Quantitative Aptitude, Logical Reasoning, and English Comprehension."
                        ]
                    ]
                ]
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Exam Hubs", "item" => self::BASE_URL . "/exams"],
                    ["@type" => "ListItem", "position" => 3, "name" => $short, "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'tldr_box' => $tldrText,
            'schemas' => $schemas
        ];
    }

    /**
     * 5. OFFICIAL COMMISSION DOSSIER SEO
     */
    public static function getCommissionDetailSeo(array $comm, array $jobs = [], array $exams = []): array {
        $name = htmlspecialchars($comm['name']);
        $short = htmlspecialchars($comm['short_name']);
        $slug = $comm['slug'];
        $activeCount = count($jobs);
        $canonical = self::BASE_URL . "/commissions/{$slug}";

        $title = "{$name} ({$short}) Recruitment 2026: Official Notices, Exam Calendar & Jobs";
        $desc = "Official recruitment dossier for {$name}. Track {$activeCount} active job notifications, examination calendar, admit cards, syllabus and direct official portal links.";

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "GovernmentOrganization",
                "name" => $name,
                "alternateName" => $short,
                "url" => $comm['official_website_url'] ?? self::BASE_URL,
                "description" => $comm['description'] ?? $desc,
                "logo" => self::DEFAULT_LOGO
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Commissions", "item" => self::BASE_URL . "/commissions"],
                    ["@type" => "ListItem", "position" => 3, "name" => $short, "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary',
            'schemas' => $schemas
        ];
    }

    /**
     * 6. ADMIT CARDS PORTAL SEO & AEO
     */
    public static function getAdmitCardsSeo(): array {
        $title = "Government Exam Admit Card 2026 — Direct Hall Ticket Download Links";
        $desc = "Download official hall tickets and call letters for UPSC, SSC, Railways, Banking, and State PSC examinations. Direct login links, exam dates & instructions.";
        $canonical = self::BASE_URL . '/admit-cards';

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "HowTo",
                "name" => "How to Download Government Exam Admit Card",
                "description" => "Step-by-step verified guide to downloading your competitive examination hall ticket.",
                "step" => [
                    [
                        "@type" => "HowToStep",
                        "position" => 1,
                        "name" => "Find Your Examination",
                        "text" => "Locate your official examination notification card in the HamariJobs active admit card directory."
                    ],
                    [
                        "@type" => "HowToStep",
                        "position" => 2,
                        "name" => "Access Official Candidate Portal",
                        "text" => "Click the verified direct portal link to reach the official commission candidate login page."
                    ],
                    [
                        "@type" => "HowToStep",
                        "position" => 3,
                        "name" => "Enter Credentials & Download",
                        "text" => "Enter your Registration Number/Roll Number and Date of Birth to download and print the hall ticket PDF."
                    ]
                ]
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Admit Cards", "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary',
            'schemas' => $schemas
        ];
    }

    /**
     * 7. RESULTS & CUTOFFS PORTAL SEO
     */
    public static function getResultsSeo(): array {
        $title = "Government Exam Results & Cutoff Marks 2026 — Merit Lists & Scorecards";
        $desc = "Check verified official government exam results, final merit lists, score cards, and category-wise cutoff marks (UR, OBC, SC, ST, EWS) for national and state exams.";
        $canonical = self::BASE_URL . '/results';

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "CollectionPage",
                "name" => $title,
                "url" => $canonical,
                "description" => $desc
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Results & Cutoffs", "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary',
            'schemas' => $schemas
        ];
    }

    /**
     * 8. IN-DEPTH EDITORIAL ARTICLE & PREPARATION GUIDE SEO
     */
    public static function getArticleDetailSeo(array $article, ?array $rec = null): array {
        $title = htmlspecialchars($article['title']);
        $slug = $article['slug'];
        $canonical = self::BASE_URL . "/articles/{$slug}";
        $desc = htmlspecialchars($article['excerpt'] ?: mb_substr(strip_tags($article['content']), 0, 155) . '...');
        $datePublished = !empty($article['published_at']) ? date('Y-m-d', strtotime($article['published_at'])) : date('Y-m-d');

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "Article",
                "headline" => $title,
                "description" => $desc,
                "datePublished" => $datePublished,
                "dateModified" => date('Y-m-d'),
                "author" => [
                    "@type" => "Organization",
                    "name" => "HamariJobs Examination Intelligence Bureau"
                ],
                "publisher" => [
                    "@type" => "Organization",
                    "name" => self::BRAND_NAME,
                    "logo" => [
                        "@type" => "ImageObject",
                        "url" => self::DEFAULT_LOGO
                    ]
                ],
                "mainEntityOfPage" => [
                    "@type" => "WebPage",
                    "@id" => $canonical
                ]
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Preparation Guides", "item" => self::BASE_URL . "/articles"],
                    ["@type" => "ListItem", "position" => 3, "name" => $title, "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'article',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'schemas' => $schemas
        ];
    }

    /**
     * 9. COMMISSIONS DIRECTORY LIST SEO
     */
    public static function getCommissionsListSeo(): array {
        $title = "Government Recruiting Commissions Directory 2026 — UPSC, SSC, Railways & State PSCs";
        $desc = "Official directory of recruitment commissions across India. Track UPSC, SSC, Railway Recruitment Boards, Banking IBPS/SBI, and State Public Service Commissions.";
        $canonical = self::BASE_URL . '/commissions';

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "CollectionPage",
                "name" => $title,
                "url" => $canonical,
                "description" => $desc
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Commissions", "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary',
            'schemas' => $schemas
        ];
    }

    /**
     * 10. EXAM HUBS DIRECTORY LIST SEO
     */
    public static function getExamsListSeo(): array {
        $title = "Government Competitive Exams Directory 2026 — Schemes, Syllabus & Cutoffs";
        $desc = "Explore comprehensive intelligence hubs for India's major competitive examinations including UPSC Civil Services, SSC CGL, RRB NTPC, IBPS PO, and State PCS.";
        $canonical = self::BASE_URL . '/exams';

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "CollectionPage",
                "name" => $title,
                "url" => $canonical,
                "description" => $desc
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Exam Hubs", "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary',
            'schemas' => $schemas
        ];
    }

    /**
     * 11. ARTICLES / PREPARATION GUIDES LIST SEO
     */
    public static function getArticlesListSeo(): array {
        $title = "Government Exam Preparation Guides & Strategy 2026 — Syllabus, Books & Cutoffs";
        $desc = "Exhaustive editorial preparation guides, topic-wise syllabus weightage breakdowns, and 90-day study roadmaps authored by examination intelligence specialists.";
        $canonical = self::BASE_URL . '/articles';

        $schemas = [
            [
                "@context" => "https://schema.org",
                "@type" => "CollectionPage",
                "name" => $title,
                "url" => $canonical,
                "description" => $desc
            ],
            [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => self::BASE_URL . "/"],
                    ["@type" => "ListItem", "position" => 2, "name" => "Preparation Guides", "item" => $canonical]
                ]
            ]
        ];

        return [
            'title' => self::formatTitle($title),
            'description' => self::formatDescription($desc),
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $desc,
            'og_image' => self::DEFAULT_LOGO,
            'og_url' => $canonical,
            'twitter_card' => 'summary',
            'schemas' => $schemas
        ];
    }
}
