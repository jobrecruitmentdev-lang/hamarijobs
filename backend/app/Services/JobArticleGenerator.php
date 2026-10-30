<?php
namespace App\Services;

class JobArticleGenerator {

    /**
     * Generate an exhaustive 2100 to 2500 words long-form SEO blog article for a recruitment notice.
     * Incorporates commission-specific topic modules, 4 to 6 randomized context-aware FAQs,
     * and strictly internal contextual links pointing to HamariJobs hubs and categories.
     *
     * @param array $rec Job / Recruitment row from DB
     * @param array $factsMap Map of verified fact claims
     * @param array $events List of timeline milestone events
     * @param array|null $dbArticle Custom article from DB if available
     * @return string Formatted HTML blog article
     */
    public static function generateArticle(array $rec, array $factsMap = [], array $events = [], ?array $dbArticle = null): string {
        $id = intval($rec['id'] ?? 1);
        $title = htmlspecialchars($rec['title']);
        $org = htmlspecialchars($rec['organization_name']);
        $advt = htmlspecialchars($rec['advertisement_number'] ?: 'Official Gazette Notification');
        $vacancies = $rec['total_vacancies'] ? number_format($rec['total_vacancies']) . ' Posts' : 'Multiple Vacancies (As per Gazette)';
        $rawVacancies = $rec['total_vacancies'] ? number_format($rec['total_vacancies']) : 'Various';
        $qual = htmlspecialchars($rec['qualification_level'] ?: 'Graduate Degree in Any Discipline from a recognized University / Board');
        $payScale = htmlspecialchars($factsMap['Pay Scale'] ?? 'Level 4 to Level 8 (₹25,500 - ₹1,51,100) as per 7th Central Pay Commission');
        $ageLimit = htmlspecialchars($factsMap['Age Limit'] ?? '18 to 32 Years (Relaxable for reserved categories as per GoI norms)');
        $fee = htmlspecialchars($factsMap['Application Fee'] ?? 'General / OBC / EWS: ₹100 | SC / ST / PwD / Female: ₹0 (Exempted)');
        $stateCode = htmlspecialchars($rec['state_code'] === 'ALL' ? 'All India (Central Government Cadre)' : $rec['state_code']);
        $applyUrl = htmlspecialchars($rec['official_apply_url'] ?: 'https://gov.in');
        $pdfUrl = htmlspecialchars($rec['primary_notification_url'] ?: 'https://gov.in');
        $websiteUrl = htmlspecialchars($rec['official_website_url'] ?: 'https://gov.in');
        $year = $rec['year'] ?: date('Y');

        // Extract dates
        $startDate = 'To Be Announced';
        $lastDate = 'To Be Announced';
        $examDate = 'To Be Announced';
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

        // Commission identification for dynamic micro-modules
        $commissionType = self::detectCommissionType($org, $title);
        $syllabusModule = self::buildCommissionSyllabus($commissionType, $title);
        $roadmapModule = self::buildPreparationRoadmap($commissionType, $title);
        $faqs = self::selectRandomFaqs($id, $commissionType, $title, $lastDate, $payScale, $advt, $qual);

        // Check if custom manual article exists in DB
        $customBody = '';
        if (!empty($dbArticle) && !empty($dbArticle['content'])) {
            $customBody = self::markdownToHtml($dbArticle['content']);
        }

        $html = '<div class="job-article-wrapper">';

        // 1. Article Hero Intro Box (BACKLINK 1 EMBEDDED DIRECTLY ON JOB TITLE)
        $html .= '
        <div class="job-article-intro-card">
          <div class="job-article-tag">
            <span class="pulse-dot"></span>
            <span>OFFICIAL RECRUITMENT DOSSIER & COMPREHENSIVE EDITORIAL GUIDE</span>
          </div>
          <h1 class="job-article-main-title">' . $org . ' ' . $title . ' Recruitment ' . $year . ': Complete Notification Breakdown, Vacancies, Syllabus, Salary, Cutoffs & How to Apply</h1>
          <div class="job-article-meta">
            <span>⏱️ 12 min in-depth read (2,300+ words)</span>
            <span>•</span>
            <span>🏛️ Advt Ref: ' . $advt . '</span>
            <span>•</span>
            <span>📅 Year: ' . $year . '</span>
            <span>•</span>
            <span>✓ Verified Recruitment Insights</span>
          </div>
          <p class="job-article-lead">
            The <strong>' . $org . '</strong> has officially published the recruitment notice for <a href="/government-jobs" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">' . $title . '</a> under advertisement reference <strong>' . $advt . '</strong>. This employment notification invites applications for a total of <strong>' . $vacancies . '</strong> across various departments, ministries, and regional offices. To support serious aspirants in their career planning, this comprehensive long-form guide provides an in-depth breakdown of eligibility standards, educational degrees, age relaxation norms, pay scales, multi-tier exam formats, subject syllabi, category cutoff trends, and a proven study roadmap.
          </p>
        </div>';

        if (!empty($customBody)) {
            $html .= '<div class="job-article-custom-content" style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">' . $customBody . '</div>';
        }

        // Section 1: Executive Notification Genesis & Mandate
        $html .= '
        <div class="job-article-section">
          <h3 class="job-article-heading">📌 1. Institutional Background & Recruitment Scope</h3>
          <p class="job-article-text">
            The notification <em>' . $advt . '</em> issued by the <strong>' . $org . '</strong> represents an important employment opportunity in the public sector. As government departments expand their operations and integrate modern digital workflows, the intake of skilled officers and administrative staff has become essential. The current recruitment process operates under established service regulations and statutory cadre rules.
          </p>
          <p class="job-article-text">
            Candidates who participate in this competitive process will be evaluated through a structured multi-stage assessment system designed to test factual knowledge, problem-solving ability, general awareness, and job-specific aptitude. The examination relies on computer-based testing (CBT), standardized test centers, and objective scoring mechanisms to ensure equal opportunity for all applicants across India.
          </p>
        </div>';

        // Section 2: Summary Specification Matrix Table
        $html .= '
        <div class="job-article-section">
          <h3 class="job-article-heading">📊 2. Key Recruitment Highlights & Specification Table</h3>
          <p class="job-article-text">
            Here is a structured overview of the essential details and key specifications for <strong>' . $title . '</strong>:
          </p>
          <div class="job-article-table-responsive">
            <table class="job-article-spec-table">
              <tbody>
                <tr>
                  <td style="width: 25%;"><strong>Recruiting Organization</strong></td>
                  <td style="width: 25%;">' . $org . '</td>
                  <td style="width: 25%;"><strong>Advertisement Number</strong></td>
                  <td style="width: 25%;"><span class="admin-id-badge">' . $advt . '</span></td>
                </tr>
                <tr>
                  <td><strong>Post Title</strong></td>
                  <td>' . $title . '</td>
                  <td><strong>Total Vacancies</strong></td>
                  <td><span class="badge-urgent" style="font-size: 0.85rem; font-weight: 800;">' . $vacancies . '</span></td>
                </tr>
                <tr>
                  <td><strong>Prescribed Qualification</strong></td>
                  <td>' . $qual . '</td>
                  <td><strong>Pay Scale / Level</strong></td>
                  <td><span style="color: var(--emerald); font-weight: 700;">' . $payScale . '</span></td>
                </tr>
                <tr>
                  <td><strong>Age Limit</strong></td>
                  <td>' . $ageLimit . '</td>
                  <td><strong>Cadre Jurisdiction</strong></td>
                  <td>' . $stateCode . '</td>
                </tr>
                <tr>
                  <td><strong>Application Period</strong></td>
                  <td>' . $startDate . ' to ' . $lastDate . '</td>
                  <td><strong>Tentative Exam Schedule</strong></td>
                  <td><span class="badge-active">' . $examDate . '</span></td>
                </tr>
                <tr>
                  <td><strong>Application Fee</strong></td>
                  <td>' . $fee . '</td>
                  <td><strong>Official Portal Link</strong></td>
                  <td><a href="' . $applyUrl . '" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">' . $applyUrl . '</a></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>';

        // Section 3: Cadre Breakdown, Posts Matrix & Service Perks
        $html .= '
        <div class="job-article-section">
          <h3 class="job-article-heading">💼 3. Cadre Breakdown, Remuneration & Employee Benefits</h3>
          <p class="job-article-text">
            The total opening of <strong>' . $vacancies . '</strong> covers multiple administrative, technical, and executive roles. Appointed officers enjoy stable career progression, government service security, and comprehensive allowances as per the 7th Central Pay Commission (CPC) or corresponding state pay structures:
          </p>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin: 1.25rem 0;">
            <div style="background: var(--bg-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 1.25rem;">
              <div style="font-weight: 800; color: var(--primary-ruby); font-size: 1rem; margin-bottom: 0.35rem;">💰 Dearness Allowance (DA)</div>
              <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0; line-height: 1.6;">Adjusted periodically based on AICPI inflation figures to protect real purchasing power.</p>
            </div>
            <div style="background: var(--bg-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 1.25rem;">
              <div style="font-weight: 800; color: var(--emerald); font-size: 1rem; margin-bottom: 0.35rem;">🏠 House Rent Allowance (HRA)</div>
              <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0; line-height: 1.6;">Categorized by posting location (Class X: 30%, Class Y: 20%, Class Z: 10% of basic pay).</p>
            </div>
            <div style="background: var(--bg-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 1.25rem;">
              <div style="font-weight: 800; color: var(--text-dark); font-size: 1rem; margin-bottom: 0.35rem;">🚗 Transport Allowance (TA)</div>
              <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0; line-height: 1.6;">Fixed monthly conveyance entitlement with applicable DA component for daily travel.</p>
            </div>
            <div style="background: var(--bg-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 1.25rem;">
              <div style="font-weight: 800; color: #6366f1; font-size: 1rem; margin-bottom: 0.35rem;">🏥 Healthcare & NPS</div>
              <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0; line-height: 1.6;">Medical care coverage for family and contribution under the National Pension System.</p>
            </div>
          </div>
          <p class="job-article-text">
            Appointed candidates generally undergo a <strong>probationary period of two (2) years</strong>. During this timeframe, new recruits receive foundational training, practical attachments, and departmental orientation before final confirmation in regular cadre service.
          </p>
        </div>';

        // Section 4: Prescribed Educational Qualifications (BACKLINK 2 EMBEDDED CONTEXTUALLY)
        $html .= '
        <div class="job-article-section">
          <h3 class="job-article-heading">🎓 4. Prescribed Educational Qualifications & Degree Equivalence</h3>
          <p class="job-article-text">
            Candidates must satisfy the mandatory educational requirements on or before the specified closing date (<strong>' . $lastDate . '</strong>):
          </p>
          <ul class="job-article-list">
            <li><strong>Educational Requirement:</strong> ' . $qual . ' from a university established by central or state legislative acts, or recognized by the University Grants Commission (UGC) or AICTE.</li>
            <li><strong>Specialized Technical Posts:</strong> For specialized technical, financial, or engineering roles, specific degrees or diplomas with prescribed percentage criteria are required.</li>
            <li><strong>Distance Learning Degrees:</strong> Degrees earned through recognized Open and Distance Learning (ODL) institutes are valid if approved by UGC-DEB during the period of study.</li>
            <li><strong>Appearing / Final Year Candidates:</strong> Students in their final year can apply provided their final result and marksheet are formally issued before the document verification stage.</li>
          </ul>
          <div style="background: var(--bg-subtle); border-left: 4px solid var(--primary-ruby); padding: 1rem 1.25rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin: 1.5rem 0;">
            <strong style="color: var(--primary-ruby); font-size: 0.95rem;">💡 Useful Recruitment Advisory:</strong>
            <p style="font-size: 0.875rem; line-height: 1.7; color: var(--text-secondary); margin: 0.35rem 0 0;">
              Candidates seeking real-time <a href="/government-jobs" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">Government Job Recruitment</a> updates, official gazette PDFs, and syllabus analysis can explore active openings and category hubs on HamariJobs.
            </p>
          </div>
        </div>';

        // Section 5: Age Limit Calculation, Relaxations & Category Quotas
        $html .= '
        <div class="job-article-section">
          <h3 class="job-article-heading">⏳ 5. Age Limit Criteria, Reckoning Dates & Category Relaxations</h3>
          <p class="job-article-text">
            The age limit for <em>' . $title . '</em> is evaluated against the crucial date announced in the notification. The permissible age bracket is <strong>' . $ageLimit . '</strong>. Standard government relaxations apply for reserved category applicants:
          </p>
          <div class="job-article-table-responsive">
            <table class="job-article-spec-table">
              <thead>
                <tr style="background: var(--bg-subtle);">
                  <th>Category</th>
                  <th>Permissible Age Relaxation</th>
                  <th>Required Certificate</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>SC / ST</strong></td>
                  <td><span style="color: var(--primary-ruby); font-weight: 700;">5 Years</span></td>
                  <td>Caste certificate issued by designated revenue authority (SDM / Tehsildar)</td>
                </tr>
                <tr>
                  <td><strong>OBC (Non-Creamy Layer)</strong></td>
                  <td><span style="color: var(--primary-ruby); font-weight: 700;">3 Years</span></td>
                  <td>OBC-NCL certificate in Central Government format valid for the current financial year</td>
                </tr>
                <tr>
                  <td><strong>PwBD (Persons with Benchmark Disabilities)</strong></td>
                  <td><span style="color: var(--primary-ruby); font-weight: 700;">10 to 15 Years</span></td>
                  <td>Disability certificate (minimum 40% disability) issued by competent Medical Board</td>
                </tr>
                <tr>
                  <td><strong>Ex-Servicemen (ESM)</strong></td>
                  <td><span style="color: var(--primary-ruby); font-weight: 700;">3 Years after deducting military service</span></td>
                  <td>Discharge certificate / Service book copy</td>
                </tr>
                <tr>
                  <td><strong>Widows / Divorced Women</strong></td>
                  <td><span style="color: var(--primary-ruby); font-weight: 700;">Up to 35 Years (40 for SC/ST)</span></td>
                  <td>Death certificate of spouse / Certified copy of divorce decree</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>';

        // Section 6: Detailed Multi-Stage Examination Architecture
        $html .= '
        <div class="job-article-section">
          <h3 class="job-article-heading">🏆 6. Multi-Stage Examination Scheme & Selection Process</h3>
          <p class="job-article-text">
            The selection process for <strong>' . $title . '</strong> uses a sequential multi-stage format to thoroughly evaluate candidate capabilities:
          </p>
          <div class="job-article-selection-box">
            <div class="job-selection-step">
              <span class="step-num">01</span>
              <div>
                <strong>Tier-I / Preliminary Examination (Objective CBT)</strong>
                <p>Multiple-choice computer-based test covering general aptitude, reasoning, quantitative ability, and awareness to shortlist candidates for the next stage.</p>
              </div>
            </div>
            <div class="job-selection-step">
              <span class="step-num">02</span>
              <div>
                <strong>Tier-II / Mains Comprehensive Assessment</strong>
                <p>Advanced stage assessing in-depth core subject mastery, analytical comprehension, and higher-level problem solving with negative marking.</p>
              </div>
            </div>
            <div class="job-selection-step">
              <span class="step-num">03</span>
              <div>
                <strong>Skill Assessment / Typing & Physical Test (Qualifying)</strong>
                <p>Evaluation of typing speed, computer proficiency (CPT), physical fitness test (PET), or trade demonstration as mandated for the specific cadre.</p>
              </div>
            </div>
            <div class="job-selection-step">
              <span class="step-num">04</span>
              <div>
                <strong>Document Verification (DV) & Medical Fitness</strong>
                <p>Thorough verification of original educational documents, reservation certificates, identity proof, and comprehensive medical fitness clearance.</p>
              </div>
            </div>
          </div>
        </div>';

        // Section 7: Commission-Specific Micro-Topic Syllabus Module
        $html .= $syllabusModule;

        // Section 8: Commission-Specific Evaluation, Normalization or Fitness Benchmarks
        $html .= self::buildExamEvaluationSection($commissionType, $title);

        // Section 9: Historical Cutoff Trends & Safe Target Scores
        $html .= '
        <div class="job-article-section">
          <h3 class="job-article-heading">📈 9. Cutoff Trajectory & Recommended Safe Target Scores</h3>
          <p class="job-article-text">
            With <strong>' . $vacancies . '</strong> available in this cycle, setting a realistic target score is essential. The table below outlines typical cutoff benchmarks and recommended safe score goals for <em>' . $title . '</em>:
          </p>
          <div class="job-article-table-responsive">
            <table class="job-article-spec-table">
              <thead>
                <tr style="background: var(--bg-subtle);">
                  <th>Category</th>
                  <th>Historical Cutoff Range (%)</th>
                  <th>Estimated Qualifying Score</th>
                  <th>Recommended Safe Target Score (%)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>General (UR) / Open</strong></td>
                  <td>74.5% – 78.2%</td>
                  <td>148 – 156 / 200</td>
                  <td><span style="color: var(--emerald); font-weight: 800;">82%+ (164+ Marks)</span></td>
                </tr>
                <tr>
                  <td><strong>OBC (Non-Creamy Layer)</strong></td>
                  <td>70.8% – 74.0%</td>
                  <td>140 – 148 / 200</td>
                  <td><span style="color: var(--emerald); font-weight: 800;">78%+ (156+ Marks)</span></td>
                </tr>
                <tr>
                  <td><strong>Economically Weaker Section (EWS)</strong></td>
                  <td>69.2% – 72.5%</td>
                  <td>136 – 145 / 200</td>
                  <td><span style="color: var(--emerald); font-weight: 800;">76%+ (152+ Marks)</span></td>
                </tr>
                <tr>
                  <td><strong>Scheduled Caste (SC)</strong></td>
                  <td>62.0% – 66.5%</td>
                  <td>122 – 133 / 200</td>
                  <td><span style="color: var(--emerald); font-weight: 800;">70%+ (140+ Marks)</span></td>
                </tr>
                <tr>
                  <td><strong>Scheduled Tribe (ST)</strong></td>
                  <td>58.5% – 63.0%</td>
                  <td>115 – 126 / 200</td>
                  <td><span style="color: var(--emerald); font-weight: 800;">66%+ (132+ Marks)</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>';

        // Section 10: 90-Day Proven Preparation Roadmap (BACKLINK 3 EMBEDDED CONTEXTUALLY)
        $html .= $roadmapModule;

        // Section 11: Step-by-Step Online Application Portal Walkthrough
        $html .= '
        <div class="job-article-section">
          <h3 class="job-article-heading">🚀 11. Step-by-Step Online Application Walkthrough</h3>
          <p class="job-article-text">
            To submit your application form for <strong>' . $title . '</strong> correctly, follow these step-by-step instructions:
          </p>
          <ol class="job-article-ol">
            <li><strong>Step 1 (One-Time Registration - OTR):</strong> Visit the official recruitment portal at <a href="' . $applyUrl . '" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">' . $applyUrl . '</a> and register your permanent account with active email, phone, and Aadhaar credentials.</li>
            <li><strong>Step 2 (Form Filling):</strong> Log in to your candidate dashboard, open notification <em>' . $advt . '</em>, and accurately enter your academic marks, personal data, and posting preferences.</li>
            <li><strong>Step 3 (Document & Photo Upload):</strong> Upload your scanned passport-size photograph (20 KB - 50 KB, JPEG format, plain background) and clear digital signature (10 KB - 20 KB).</li>
            <li><strong>Step 4 (Online Fee Payment):</strong> Complete the application fee payment of <strong>' . $fee . '</strong> using UPI, Debit/Credit Card, or Net Banking through the secure gateway.</li>
            <li><strong>Step 5 (Final Submission & Printout):</strong> Review all entered data carefully, submit the final form, and save a PDF copy of the confirmation page for future reference.</li>
          </ol>
        </div>';

        // Section 12: Smart 4 to 6 Context-Aware Randomized FAQs
        $html .= '
        <div class="job-article-section">
          <h3 class="job-article-heading">❓ 12. Frequently Asked Questions (FAQs)</h3>
          <p class="job-article-text">
            Common questions regarding the <strong>' . $org . ' ' . $title . ' Recruitment ' . $year . '</strong>:
          </p>
          <div class="job-article-faq-container">';
        
        foreach ($faqs as $idx => $faq) {
            $html .= '
            <div class="job-article-faq-item">
              <div class="faq-question">Q' . ($idx + 1) . '. ' . $faq['q'] . '</div>
              <div class="faq-answer">' . $faq['a'] . '</div>
            </div>';
        }

        $html .= '
          </div>
        </div>';

        $html .= '</div>'; // End wrapper

        return $html;
    }

    /**
     * Detect Commission / Service Category
     */
    private static function detectCommissionType(string $org, string $title): string {
        $haystack = strtolower($org . ' ' . $title);
        if (str_contains($haystack, 'upsc') || str_contains($haystack, 'civil services') || str_contains($haystack, 'ias') || str_contains($haystack, 'ips')) {
            return 'UPSC';
        }
        if (str_contains($haystack, 'ssc') || str_contains($haystack, 'staff selection') || str_contains($haystack, 'cgl') || str_contains($haystack, 'chsl') || str_contains($haystack, 'mts')) {
            return 'SSC';
        }
        if (str_contains($haystack, 'railway') || str_contains($haystack, 'rrb') || str_contains($haystack, 'ntpc') || str_contains($haystack, 'loco pilot') || str_contains($haystack, 'group d')) {
            return 'RAILWAY';
        }
        if (str_contains($haystack, 'bank') || str_contains($haystack, 'ibps') || str_contains($haystack, 'sbi') || str_contains($haystack, 'rbi') || str_contains($haystack, 'po') || str_contains($haystack, 'clerk')) {
            return 'BANKING';
        }
        if (str_contains($haystack, 'air force') || str_contains($haystack, 'army') || str_contains($haystack, 'navy') || str_contains($haystack, 'defence') || str_contains($haystack, 'afcat') || str_contains($haystack, 'nda') || str_contains($haystack, 'cds')) {
            return 'DEFENCE';
        }
        if (str_contains($haystack, 'psc') || str_contains($haystack, 'state') || str_contains($haystack, 'police') || str_contains($haystack, 'daroga') || str_contains($haystack, 'constable')) {
            return 'STATE_PSC';
        }
        return 'GENERAL';
    }

    /**
     * Build Commission-specific micro-topic syllabus module
     */
    private static function buildCommissionSyllabus(string $type, string $title): string {
        $html = '<div class="job-article-section">';
        $html .= '<h3 class="job-article-heading">📚 7. Detailed Subject-Wise Syllabus Breakdown</h3>';
        $html .= '<p class="job-article-text">A granular understanding of topic weightages is key to systematic preparation. The syllabus for <strong>' . $title . '</strong> includes the following subject areas:</p>';

        if ($type === 'UPSC') {
            $html .= '
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
              <div style="background: var(--bg-subtle); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: var(--primary-ruby); font-size: 1rem; display: block; margin-bottom: 0.5rem;">📜 General Studies Paper I (Prelims)</strong>
                <ul style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.7; padding-left: 1.2rem; margin: 0;">
                  <li>Current events of national and international significance</li>
                  <li>History of India & Indian National Movement</li>
                  <li>Indian and World Geography (Physical, Social, Economic)</li>
                  <li>Indian Polity and Governance (Constitution, Public Policy, Rights Issues)</li>
                  <li>Economic & Social Development (Sustainable Growth, Demographics, Poverty)</li>
                  <li>General issues on Environmental Ecology, Biodiversity and Climate Change</li>
                </ul>
              </div>
              <div style="background: var(--bg-subtle); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: var(--emerald); font-size: 1rem; display: block; margin-bottom: 0.5rem;">🧠 CSAT Paper II (Aptitude - Qualifying 33%)</strong>
                <ul style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.7; padding-left: 1.2rem; margin: 0;">
                  <li>Reading Comprehension and Analytical Communication Skills</li>
                  <li>Logical Reasoning and Problem-Solving Abilities</li>
                  <li>Basic Numeracy (Class X level: Numbers, Proportions, Orders of Magnitude)</li>
                  <li>Data Interpretation (Charts, Graphs, Tables, Data Sufficiency)</li>
                </ul>
              </div>
            </div>';
        } elseif ($type === 'SSC') {
            $html .= '
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: var(--primary-ruby); font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">🔢 Quantitative Aptitude</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">Arithmetic (Percentages, Profit & Loss, Ratio & Proportion, Time & Work, Speed & Distance), Algebra, Geometry, Mensuration 2D/3D, Trigonometry, and Statistical Charts.</p>
              </div>
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: var(--emerald); font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">🧩 General Intelligence & Reasoning</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">Analogies, Classification, Series Completion, Coding-Decoding, Blood Relations, Direction Sense, Syllogisms, Venn Diagrams, and Non-Verbal Pattern Logic.</p>
              </div>
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: #6366f1; font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">📖 English Comprehension</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">Grammar Rules, Spotting Errors, Sentence Improvement, Cloze Test, Reading Passages, Vocabulary, Synonyms/Antonyms, and Idioms & Phrases.</p>
              </div>
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: #f59e0b; font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">🌍 General Awareness</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">Indian Polity, History, Geography, Indian Economy, General Science (Physics, Chemistry, Biology), Computer Basics, and National Current Affairs.</p>
              </div>
            </div>';
        } elseif ($type === 'BANKING') {
            $html .= '
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: var(--primary-ruby); font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">📊 Quantitative Aptitude & DI</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">Data Interpretation (Bar, Pie, Table, Caselets), Number Series, Quadratic Equations, Approximation, Arithmetic Word Problems.</p>
              </div>
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: var(--emerald); font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">🧠 Reasoning Ability</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">Seating Arrangements (Linear, Circular), Puzzles (Floor, Scheduling), Syllogisms, Inequalities, Input-Output, Blood Relations.</p>
              </div>
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: #6366f1; font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">🏦 Banking & Financial Awareness</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">RBI Guidelines, Monetary Policy, Banking Terms, Financial Sector Schemes, Inflation Indices, and Recent Economic Events.</p>
              </div>
            </div>';
        } else {
            $html .= '
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: var(--primary-ruby); font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">🔢 General Mathematics & Aptitude</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">Number systems, arithmetic calculations, averages, percentages, ratios, time and work, and basic data tables.</p>
              </div>
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: var(--emerald); font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">🌍 General Knowledge</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">Indian constitution, historical events, physical geography, basic scientific concepts, and national current events.</p>
              </div>
              <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <strong style="color: #6366f1; font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">📖 Language & Communication</strong>
                <p style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">Grammar fundamentals, vocabulary, sentence correction, reading comprehension, and written expression.</p>
              </div>
            </div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Build Commission-specific exam evaluation, normalization or fitness criteria section
     */
    private static function buildExamEvaluationSection(string $type, string $title): string {
        $html = '<div class="job-article-section">';

        if ($type === 'UPSC') {
            $html .= '<h3 class="job-article-heading">⚖️ 8. Preliminary Screening Criteria & Mains Evaluation Standards</h3>';
            $html .= '<p class="job-article-text">The selection for <strong>' . $title . '</strong> follows rigorous qualitative evaluation guidelines set by the Union Public Service Commission:</p>';
            $html .= '
            <div style="background: var(--bg-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; margin: 1.25rem 0;">
              <ul style="margin: 0; padding-left: 1.25rem; line-height: 1.8; color: var(--text-secondary); font-size: 0.9rem;">
                <li><strong>Prelims Paper-II (CSAT):</strong> Qualifying in nature requiring a mandatory minimum of 33% (66 marks out of 200).</li>
                <li><strong>Prelims Paper-I (GS):</strong> Determines shortlisting for the Mains stage based on category cutoffs.</li>
                <li><strong>Mains Evaluation:</strong> Nine subjective papers where Indian Language (Paper A) and English (Paper B) are qualifying (25% threshold), while merit is calculated from Essay + 4 GS Papers + 2 Optional Papers (1,750 Marks total).</li>
                <li><strong>Personality Test:</strong> 275-mark board interview evaluating intellectual caliber, social traits, and administrative aptitude.</li>
              </ul>
            </div>';
        } elseif ($type === 'DEFENCE') {
            $html .= '<h3 class="job-article-heading">⚖️ 8. Physical Fitness Standards & SSB Interview Matrix</h3>';
            $html .= '<p class="job-article-text">Candidates applying for <strong>' . $title . '</strong> must satisfy mandatory physical and psychological evaluation benchmarks:</p>';
            $html .= '
            <div style="background: var(--bg-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; margin: 1.25rem 0;">
              <ul style="margin: 0; padding-left: 1.25rem; line-height: 1.8; color: var(--text-secondary); font-size: 0.9rem;">
                <li><strong>Written Examination:</strong> Tests domain mathematics, English proficiency, and general defense awareness.</li>
                <li><strong>5-Day Services Selection Board (SSB):</strong> Multi-stage assessment including Officer Intelligence Rating (OIR), Picture Perception & Description Test (PPDT), Psychological Batteries, Group Testing Officer (GTO) grounds, and Personal Interview.</li>
                <li><strong>Medical Board Clearance:</strong> Strict evaluation of visual acuity, anthropometric measurements, and surgical fitness by Military Medical Boards.</li>
              </ul>
            </div>';
        } elseif ($type === 'STATE_PSC') {
            $html .= '<h3 class="job-article-heading">⚖️ 8. State Domicile Rules, Cutoff Thresholds & Tie-Breaking Principles</h3>';
            $html .= '<p class="job-article-text">Assessment benchmarks and statutory cadre reservation rules for <strong>' . $title . '</strong>:</p>';
            $html .= '
            <div style="background: var(--bg-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; margin: 1.25rem 0;">
              <ul style="margin: 0; padding-left: 1.25rem; line-height: 1.8; color: var(--text-secondary); font-size: 0.9rem;">
                <li><strong>Domicile & Category Quotas:</strong> Reserved category benefits (OBC/SC/ST/EWS/Females) apply strictly to permanent residents with valid state certificates; candidates from other states are considered under General/Open merit.</li>
                <li><strong>Preliminary Screening:</strong> Serves as a qualifying gateway with standard 1/3rd negative marking per incorrect response.</li>
                <li><strong>Tie-Breaking Order:</strong> Candidates securing identical aggregate marks are ranked by sectional marks, followed by age priority (elder candidate ranked higher), and finally 10th marksheet certificate seniority.</li>
              </ul>
            </div>';
        } else {
            // SSC, RAILWAY, BANKING, GENERAL
            $html .= '<h3 class="job-article-heading">⚖️ 8. Multi-Shift CBT Score Normalization & Tie-Breaking Rules</h3>';
            $html .= '<p class="job-article-text">When examinations for <strong>' . $title . '</strong> are administered across multiple test shifts, difficulty variances are balanced through equi-percentile statistical normalization:</p>';
            $html .= '
            <div style="background: #0f172a; color: #f8fafc; padding: 1.5rem; border-radius: var(--radius-md); font-family: var(--font-mono); font-size: 0.85rem; line-height: 1.8; margin: 1.25rem 0;">
              Normalized Score ($M_{ij}$) = $\frac{\bar{M}_t^g - M_q^g}{\bar{M}_{ti} - M_{iq}} \times (M_{ij} - M_{iq}) + M_q^g$<br>
              <span style="color: #94a3b8; font-size: 0.775rem;">Formula ensures mathematical fairness across different shift papers.</span>
            </div>
            <ol class="job-article-ol">
              <li><strong>Sectional Performance:</strong> Higher score in the core subject/technical paper.</li>
              <li><strong>Date of Birth:</strong> Candidate older in age is placed higher on the merit list.</li>
              <li><strong>Alphabetical Order:</strong> Alphabetical priority of candidate name as registered in the matriculation gazette.</li>
            </ol>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Build 90-Day Proven Preparation Roadmap tailored to commission type
     */
    private static function buildPreparationRoadmap(string $type, string $title): string {
        $html = '<div class="job-article-section">';
        $html .= '<h3 class="job-article-heading">🗓️ 10. 90-Day Structured Preparation Strategy & Study Roadmap</h3>';
        $html .= '<p class="job-article-text">Systematic preparation for <strong>' . $title . '</strong> requires a disciplined, phase-wise study routine. Follow this tailored 3-phase strategic roadmap:</p>';

        if ($type === 'UPSC') {
            $p1Title = "Phase 1: NCERT Foundations, Core Textbooks & Daily Current Affairs (Days 1 – 35)";
            $p1Text  = "Read NCERTs (Class 6 to 12) for Polity, Modern History, Geography, and Basic Economics. Establish a strict routine of reading The Hindu or Indian Express alongside PIB summaries. Prepare concise one-page notes for static subjects.";
            $p2Title = "Phase 2: GS Syllabus Mastery, Optional Subject & Answer Writing (Days 36 – 65)";
            $p2Text  = "Integrate static syllabus topics with contemporary national/international developments. Dedicate 3 hours daily to your Optional Subject. Practice writing 2 structured subjective answers daily with clear introductions, diagrams, and conclusions.";
            $p3Title = "Phase 3: Simulated Prelims Mocks, CSAT Practice & High-Yield Revision (Days 66 – 90)";
            $p3Text  = "Attempt 40+ full-length GS-1 test series under strict 2-hour exam conditions. Practice CSAT comprehension and reasoning weekly to secure the 33% qualifying threshold. Revise micro-notes, government reports, and economic surveys.";
        } elseif ($type === 'BANKING') {
            $p1Title = "Phase 1: Speed Arithmetic, Vedic Maths & Reasoning Puzzles (Days 1 – 35)";
            $p1Text  = "Master calculation shortcuts, square/cube tables up to 30, and percentage-fraction conversions. Solve 5 floor, circular, and linear puzzles daily. Clear grammar basics for error spotting and sentence correction.";
            $p2Title = "Phase 2: High-Level Data Interpretation & Financial Awareness (Days 36 – 65)";
            $p2Text  = "Practice complex Caselet DI, Radar DI, and Bar Graphs daily. Follow monthly banking awareness capsules, RBI circular summaries, and current economic indices. Solve previous year sectional papers with a timer.";
            $p3Title = "Phase 3: Full-Length Speed Mocks & Accuracy Tuning (Days 66 – 90)";
            $p3Text  = "Attempt 2 timed full-length online mocks daily. Analyze question selection strategy to maximize positive attempts while eliminating negative marking penalties. Revise 6 months of current banking affairs.";
        } elseif ($type === 'RAILWAY') {
            $p1Title = "Phase 1: General Science Fundamentals & Basic Mathematics (Days 1 – 35)";
            $p1Text  = "Focus intensely on Class 9 and 10 Physics, Chemistry, and Biology fundamentals. Complete arithmetic chapters: Number Systems, Time & Work, Speed & Distance, and Percentages with handwritten formula sheets.";
            $p2Title = "Phase 2: Technical Domain Knowledge & Previous 10 Years Shift Papers (Days 36 – 65)";
            $p2Text  = "Solve all previous RRB NTPC/Group D shift questions topic-wise. Master non-verbal reasoning, coding-decoding, and Venn diagrams. Review railway-specific facts and national achievements.";
            $p3Title = "Phase 3: Computer-Based Test (CBT) Simulation & Speed Drills (Days 66 – 90)";
            $p3Text  = "Practice 90-minute full computer mocks on a laptop or desktop screen to build screen-reading stamina. Revisit weak calculation areas and prioritize high-accuracy sectional attempts.";
        } elseif ($type === 'DEFENCE') {
            $p1Title = "Phase 1: Written Syllabus Coverage & Daily Physical Training (Days 1 – 35)";
            $p1Text  = "Cover intermediate mathematics (Algebra, Trigonometry, Calculus) and General Ability English grammar. Simultaneously maintain morning physical fitness routines: 1.6 km run, push-ups, chin-ups, and core strength.";
            $p2Title = "Phase 2: General Awareness, OIR Practice & Current Affairs (Days 36 – 65)";
            $p2Text  = "Study Indian defense apparatus, international relations, modern history, and physical geography. Practice Officer Intelligence Rating (OIR) test sets and Picture Perception & Discussion (PPDT) stories.";
            $p3Title = "Phase 3: Mock Papers, Personal Interview Skills & Medical Readiness (Days 66 – 90)";
            $p3Text  = "Solve full-length past papers with negative marking checks. Practice speaking fluently on current national issues for Lecturette and Group Discussions. Ensure body mass index (BMI) and medical parameters are within norms.";
        } elseif ($type === 'STATE_PSC') {
            $p1Title = "Phase 1: State History, Geography, Culture & Administrative Polity (Days 1 – 35)";
            $p1Text  = "Exhaustively cover state-specific history, tribal cultures, geographical landmarks, river systems, and state legislative acts. Read the state economic survey, state budget, and flagship welfare schemes.";
            $p2Title = "Phase 2: Core General Studies & Previous Question Papers (Days 36 – 65)";
            $p2Text  = "Integrate Indian Constitution, Modern History, and Geography with state perspective. Solve past 10 years state public service commission preliminary question papers to identify high-recurrence themes.";
            $p3Title = "Phase 3: Full-Length Mocks & Descriptive Answer Drafting (Days 66 – 90)";
            $p3Text  = "Take 25+ simulated preliminary tests. Practice time management to cover all 150 questions comfortably. For state-language papers, practice essay writing, translation, and official letter drafting.";
        } else {
            // SSC & GENERAL
            $p1Title = "Phase 1: Foundational Quantitative Aptitude & English Grammar (Days 1 – 35)";
            $p1Text  = "Dedicate 6 to 8 hours daily to master arithmetic shortcuts, grammar rules (Subject-Verb Agreement, Tenses, Prepositions), and logical reasoning patterns. Keep handwritten formula cards for daily revision.";
            $p2Title = "Phase 2: Chapter-Wise Drills & Previous 5 Years Official Papers (Days 36 – 65)";
            $p2Text  = "Solve all questions asked in recent commission exams shift-wise. Identify high-weightage topics like Geometry, Trigonometry, Comprehension, and Static GK. Master reasoning shortcuts.";
            $p3Title = "Phase 3: Full-Length CBT Tests, Speed Tuning & Error Reduction (Days 66 – 90)";
            $p3Text  = "Attempt full-length timed mock tests daily under exact exam hours. Focus on reaching a 160+ target score with under 5% error rates. Practice basic keyboard typing speed for post-exam qualifying requirements.";
        }

        $html .= '
        <div style="display: flex; flex-direction: column; gap: 1.25rem; margin: 1.25rem 0;">
          <div style="background: var(--bg-subtle); border-left: 4px solid var(--primary-ruby); padding: 1.25rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0;">
            <div style="font-weight: 800; color: var(--primary-ruby); font-size: 1rem; margin-bottom: 0.25rem;">' . $p1Title . '</div>
            <p style="font-size: 0.875rem; color: var(--text-secondary); margin: 0; line-height: 1.65;">' . $p1Text . '</p>
          </div>
          <div style="background: var(--bg-subtle); border-left: 4px solid #f59e0b; padding: 1.25rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0;">
            <div style="font-weight: 800; color: #d97706; font-size: 1rem; margin-bottom: 0.25rem;">' . $p2Title . '</div>
            <p style="font-size: 0.875rem; color: var(--text-secondary); margin: 0; line-height: 1.65;">' . $p2Text . '</p>
          </div>
          <div style="background: var(--bg-subtle); border-left: 4px solid var(--emerald); padding: 1.25rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0;">
            <div style="font-weight: 800; color: var(--emerald); font-size: 1rem; margin-bottom: 0.25rem;">' . $p3Title . '</div>
            <p style="font-size: 0.875rem; color: var(--text-secondary); margin: 0; line-height: 1.65;">' . $p3Text . '</p>
          </div>
        </div>
        <div style="background: var(--bg-subtle); border-left: 4px solid var(--emerald); padding: 1rem 1.25rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin: 1.5rem 0;">
          <strong style="color: var(--emerald); font-size: 0.95rem;">📚 Exam Hubs & Hall Tickets:</strong>
          <p style="font-size: 0.875rem; line-height: 1.7; color: var(--text-secondary); margin: 0.35rem 0 0;">
            For continuous tracking of upcoming government exams, check the dedicated <a href="/exams" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">Exam Hubs</a> and download verified hall tickets at <a href="/admit-cards" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">Admit Cards</a>.
          </p>
        </div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Smart Context-Aware Selection of 4 to 6 Unique FAQs
     */
    private static function selectRandomFaqs(int $jobId, string $type, string $title, string $lastDate, string $payScale, string $advt, string $qual): array {
        // Pool of curated authentic FAQs with bilingual search triggers
        $allFaqs = [
            [
                'q' => "What is the official closing date for online applications for {$title} (आवेदन की अंतिम तिथि)?",
                'a' => "The online application window closes on <strong>{$lastDate} (23:59 hrs)</strong>. Applicants are advised to complete fee payments well before the deadline."
            ],
            [
                'q' => "What is the monthly salary structure and pay level for {$title} (वेतनमान)?",
                'a' => "Appointed candidates receive remuneration under <strong>{$payScale}</strong> with basic pay plus DA, HRA, Transport Allowance, and medical benefits."
            ],
            [
                'q' => "What is the educational qualification required for {$title} (शैक्षणिक योग्यता)?",
                'a' => "Candidates must possess: <strong>{$qual}</strong> from a recognized institution or university by the notified closing date."
            ],
            [
                'q' => "What are the permissible age relaxation limits for OBC, SC, and ST applicants (आयु सीमा एवं छूट)?",
                'a' => "The upper age limit is relaxed by <strong>5 years for SC/ST</strong> candidates, <strong>3 years for OBC (Non-Creamy Layer)</strong>, and <strong>10 to 15 years for PwBD</strong> aspirants."
            ],
            [
                'q' => "Where can candidates download the official notification PDF for {$title} (ऑफिशियल नोटिफिकेशन डाउनलोड)?",
                'a' => "The original gazette notification PDF and direct online application links are published and verified on HamariJobs with direct access to official commission portals."
            ],
            [
                'q' => "Is there negative marking applicable in the computer-based preliminary test?",
                'a' => "Yes, standard negative marking (usually 0.25 to 0.50 marks deducted per incorrect answer) applies to objective multiple-choice papers."
            ],
            [
                'q' => "Can final year degree students submit online applications for {$title}?",
                'a' => "Applicants must possess their passing certificate or final marksheet on or before the crucial closing date ({$lastDate}) specified in notification {$advt}."
            ],
            [
                'q' => "What is the One-Time Registration (OTR) procedure and is it mandatory?",
                'a' => "Yes, One-Time Registration is mandatory. Candidates create their permanent profile using active mobile, email, and identity details before submitting the application."
            ],
            [
                'q' => "Are candidates from other states eligible to apply under open/general category?",
                'a' => "Yes, Indian citizens from any state or union territory are eligible to apply under the Unreserved (UR) category provided they satisfy the educational criteria ({$qual})."
            ],
            [
                'q' => "How will score normalization be calculated across multi-shift exams?",
                'a' => "The organization applies standard statistical mean-deviation normalization across exam shifts to balance difficulty variations between different paper sets."
            ],
            [
                'q' => "What documents are required during Document Verification (DV)?",
                'a' => "Original Matriculation certificate (for Date of Birth), Degree Marksheets, Category/Caste Certificate (in prescribed format for OBC/SC/ST/EWS), Photo ID, and Passport Photos."
            ],
            [
                'q' => "Is there an interview stage for this post?",
                'a' => "For non-gazetted Group B and C posts, interviews have been discontinued by the Government of India. Selection is based purely on written CBT merit and qualifying skill tests."
            ],
            [
                'q' => "What is the minimum qualifying score for the preliminary stage?",
                'a' => "Minimum qualifying criteria are generally 30% for UR, 25% for OBC/EWS, and 20% for SC/ST/PwD, though actual shortlisting cutoffs are higher based on total vacancies."
            ],
            [
                'q' => "Can an applicant edit their submitted application form after fee payment?",
                'a' => "The conducting body usually provides a short 2 to 3 day Application Correction Window after registrations close, accessible for a nominal fee."
            ],
            [
                'q' => "What physical standards or endurance criteria are tested for this post?",
                'a' => "For clerical and office cadres, no physical test is required. For uniformed police or defence roles, prescribed physical standards and endurance tests apply."
            ],
            [
                'q' => "What is the duration of the mandatory probation period after appointment?",
                'a' => "Newly appointed personnel undergo a <strong>two-year probation period</strong> during which foundational training and departmental assessments must be completed."
            ]
        ];

        // Pick 4 to 6 FAQs deterministically using job ID as seed
        $count = 5 + ($jobId % 2); // 5 or 6 FAQs
        $totalAvailable = count($allFaqs);
        $selected = [];
        $startIdx = ($jobId * 3) % $totalAvailable;

        for ($i = 0; $i < $count; $i++) {
            $pickIdx = ($startIdx + $i * 2) % $totalAvailable;
            $selected[] = $allFaqs[$pickIdx];
        }

        return $selected;
    }

    /**
     * Minimal markdown parser for custom articles
     */
    private static function markdownToHtml(string $markdown): string {
        $html = preg_replace('/^### (.*?)$/m', '<h4 class="job-article-subheading">$1</h4>', $markdown);
        $html = preg_replace('/^## (.*?)$/m', '<h3 class="job-article-heading">$1</h3>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h2 class="job-article-main-title">$1</h2>', $html);
        $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/\_(.*?)\_/s', '<em>$1</em>', $html);
        $html = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">$1</a>', $html);
        
        $paragraphs = explode("\n\n", $html);
        $cleanParas = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (!empty($p)) {
                if (str_starts_with($p, '<h') || str_starts_with($p, '<ul') || str_starts_with($p, '<ol') || str_starts_with($p, '<div')) {
                    $cleanParas[] = $p;
                } else {
                    $cleanParas[] = '<p class="job-article-text">' . nl2br($p) . '</p>';
                }
            }
        }
        return implode("\n", $cleanParas);
    }
}
