-- ============================================================================
-- GOVERNMENT RECRUITMENT INTELLIGENCE PLATFORM
-- PHASE 2 EXTENSION: RECRUITMENT ENTITIES, EXAMS, QUESTION BANK & CONTENT
-- COMPATIBILITY: MySQL 8.0+ / MariaDB 10.4+ (InnoDB, utf8mb4)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. RECRUITMENTS (Master Entity grouping all related jobs, notices & events)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recruitments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recruitment_uuid` CHAR(36) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `organization_id` BIGINT UNSIGNED NULL,
    `organization_name` VARCHAR(255) NOT NULL,
    `advertisement_number` VARCHAR(100) NULL,
    `notification_number` VARCHAR(100) NULL,
    `year` INT UNSIGNED NOT NULL DEFAULT 2026,
    `total_vacancies` INT UNSIGNED NULL,
    `status` ENUM('Upcoming', 'Active', 'Exam_Phase', 'Result_Declared', 'Completed', 'Cancelled', 'Archived') NOT NULL DEFAULT 'Active',
    `primary_notification_url` VARCHAR(1024) NULL,
    `official_website_url` VARCHAR(512) NULL,
    `official_apply_url` VARCHAR(1024) NULL,
    `state_code` VARCHAR(10) NULL DEFAULT 'ALL',
    `qualification_level` VARCHAR(100) NULL,
    `summary` TEXT NULL,
    `is_verified` BOOLEAN DEFAULT TRUE,
    `verified_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_recruitment_uuid` (`recruitment_uuid`),
    UNIQUE KEY `idx_recruitment_slug` (`slug`),
    INDEX `idx_recruitment_org` (`organization_name`),
    INDEX `idx_recruitment_status` (`status`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. RECRUITMENT TIMELINE EVENTS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recruitment_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recruitment_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NULL,
    `event_type` ENUM(
        'NOTIFICATION_RELEASED',
        'APPLICATION_STARTED',
        'APPLICATION_CLOSED',
        'FEE_PAYMENT_DEADLINE',
        'CORRECTION_WINDOW_OPENED',
        'CORRECTION_WINDOW_CLOSED',
        'ADMIT_CARD_RELEASED',
        'EXAM_DATE',
        'ANSWER_KEY_RELEASED',
        'OBJECTION_WINDOW_CLOSED',
        'RESULT_DECLARED',
        'CUTOFF_RELEASED',
        'DOCUMENT_VERIFICATION',
        'MEDICAL_EXAM',
        'FINAL_MERIT_LIST',
        'CORRIGENDUM_ISSUED',
        'POSTPONED_NOTICE',
        'OTHER'
    ) NOT NULL,
    `event_title` VARCHAR(255) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'RELEASED',
    `event_date` DATE NULL,
    `event_datetime` DATETIME NULL,
    `is_tentative` BOOLEAN DEFAULT FALSE,
    `details` TEXT NULL,
    `reference_url` VARCHAR(1024) NULL,
    `reference_document_hash` CHAR(64) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_re_recruitment` FOREIGN KEY (`recruitment_id`) REFERENCES `recruitments`(`id`) ON DELETE CASCADE,
    INDEX `idx_re_type_date` (`recruitment_id`, `event_type`, `event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. EXAMS (Exam Entity Hub)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exams` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_uuid` CHAR(36) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `short_name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `conducting_body` VARCHAR(255) NOT NULL,
    `category` ENUM('Civil Services', 'Staff Selection', 'Railways', 'Banking', 'Defense', 'Police', 'Teaching', 'Engineering', 'State PSC', 'Other') NOT NULL DEFAULT 'Other',
    `frequency` VARCHAR(50) DEFAULT 'Annual',
    `overview` LONGTEXT NULL,
    `eligibility_summary` TEXT NULL,
    `age_limit_summary` TEXT NULL,
    `selection_stages_summary` TEXT NULL,
    `preparation_strategy` LONGTEXT NULL,
    `official_website` VARCHAR(512) NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_exam_uuid` (`exam_uuid`),
    UNIQUE KEY `idx_exam_slug` (`slug`),
    INDEX `idx_exam_body` (`conducting_body`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. EXAM PHASES / TIERS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_phases` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `phase_name` VARCHAR(100) NOT NULL,
    `phase_order` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `mode` ENUM('Online (CBT)', 'Offline (OMR)', 'Pen and Paper', 'Physical Test', 'Interview', 'Skill Test') DEFAULT 'Online (CBT)',
    `is_qualifying` BOOLEAN DEFAULT FALSE,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ep_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `idx_exam_phase_order` (`exam_id`, `phase_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. EXAM PATTERNS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_patterns` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `phase_id` BIGINT UNSIGNED NOT NULL,
    `subject_name` VARCHAR(150) NOT NULL,
    `num_questions` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `max_marks` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    `negative_marking` VARCHAR(50) DEFAULT '0.25 marks',
    `language` VARCHAR(100) DEFAULT 'English & Hindi',
    `order_index` TINYINT UNSIGNED DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_epat_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_epat_phase` FOREIGN KEY (`phase_id`) REFERENCES `exam_phases`(`id`) ON DELETE CASCADE,
    INDEX `idx_epat_phase` (`phase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. EXAM SYLLABUS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_syllabus` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `phase_id` BIGINT UNSIGNED NOT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `topic` VARCHAR(255) NOT NULL,
    `subtopics` JSON NULL,
    `weightage_percentage` DECIMAL(5,2) NULL,
    `difficulty_tier` ENUM('Easy', 'Moderate', 'Hard', 'Comprehensive') DEFAULT 'Moderate',
    `order_index` SMALLINT UNSIGNED DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_esyl_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_esyl_phase` FOREIGN KEY (`phase_id`) REFERENCES `exam_phases`(`id`) ON DELETE CASCADE,
    INDEX `idx_esyl_topic` (`exam_id`, `subject`, `topic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. CUTOFF RECORDS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cutoff_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `recruitment_id` BIGINT UNSIGNED NULL,
    `year` INT UNSIGNED NOT NULL,
    `phase_id` BIGINT UNSIGNED NULL,
    `category` ENUM('UR', 'OBC', 'EWS', 'SC', 'ST', 'PwD', 'Ex-Servicemen', 'All') NOT NULL DEFAULT 'UR',
    `cutoff_marks` DECIMAL(7,2) NOT NULL,
    `total_marks` DECIMAL(7,2) NOT NULL,
    `qualifying_candidates` INT UNSIGNED NULL,
    `official_notice_url` VARCHAR(1024) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cut_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    INDEX `idx_cut_exam_year` (`exam_id`, `year`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. QUESTION BANK
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `question_bank` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `phase_id` BIGINT UNSIGNED NULL,
    `year` INT UNSIGNED NOT NULL,
    `shift` VARCHAR(50) NULL,
    `question_number` SMALLINT UNSIGNED NOT NULL,
    `question_text` TEXT NOT NULL,
    `option_a` TEXT NOT NULL,
    `option_b` TEXT NOT NULL,
    `option_c` TEXT NOT NULL,
    `option_d` TEXT NOT NULL,
    `correct_option` CHAR(1) NULL,
    `explanation` TEXT NULL,
    `subject` VARCHAR(100) NOT NULL,
    `topic` VARCHAR(150) NOT NULL,
    `difficulty` ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
    `source_document_url` VARCHAR(1024) NULL,
    `source_page` SMALLINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_qb_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    INDEX `idx_qb_exam_year` (`exam_id`, `year`, `subject`),
    INDEX `idx_qb_topic` (`subject`, `topic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. INTELLIGENCE ARTICLES & GUIDES
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `articles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_uuid` CHAR(36) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `article_type` ENUM(
        'Notification_Guide',
        'Eligibility_Guide',
        'Syllabus_Breakdown',
        'Exam_Pattern',
        'Preparation_Strategy',
        'Cutoff_Analysis',
        'Previous_Year_Analysis',
        'Admit_Card_Guide',
        'Answer_Key_Guide',
        'Result_Guide',
        'General_Recruitment_News'
    ) NOT NULL DEFAULT 'Notification_Guide',
    `recruitment_id` BIGINT UNSIGNED NULL,
    `exam_id` BIGINT UNSIGNED NULL,
    `job_id` BIGINT UNSIGNED NULL,
    `content` LONGTEXT NOT NULL,
    `excerpt` TEXT NULL,
    `focus_keywords` VARCHAR(512) NULL,
    `quality_score` TINYINT UNSIGNED DEFAULT 85,
    `fact_check_status` ENUM('Pending', 'Verified_100', 'Requires_Correction', 'Rejected') DEFAULT 'Verified_100',
    `status` ENUM('Draft', 'Review', 'Approved', 'Published', 'Archived') DEFAULT 'Published',
    `view_count` INT UNSIGNED DEFAULT 0,
    `reading_time_minutes` TINYINT UNSIGNED DEFAULT 5,
    `published_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_article_uuid` (`article_uuid`),
    UNIQUE KEY `idx_article_slug` (`slug`),
    INDEX `idx_article_type_status` (`article_type`, `status`),
    INDEX `idx_article_recruitment` (`recruitment_id`),
    INDEX `idx_article_exam` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. ARTICLE VERSIONS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `article_versions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` BIGINT UNSIGNED NOT NULL,
    `version_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `title` VARCHAR(255) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `changed_summary` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_av_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
    INDEX `idx_av_version` (`article_id`, `version_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. FACT CLAIMS & EVIDENCE ANCHORS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fact_claims` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` ENUM('Job', 'Recruitment', 'Exam', 'Article') NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `claimed_value` TEXT NOT NULL,
    `source_document_url` VARCHAR(1024) NULL,
    `source_page` SMALLINT UNSIGNED NULL,
    `evidence_snippet` TEXT NULL,
    `confidence_score` DECIMAL(5,2) DEFAULT 100.00,
    `verified_by` VARCHAR(100) DEFAULT 'AI_Extractor_v2',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fc_entity` (`entity_type`, `entity_id`, `field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. CRAWL RUNS & CRAWL ITEMS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `crawl_runs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_id` BIGINT UNSIGNED NOT NULL,
    `run_uuid` CHAR(36) NOT NULL,
    `trigger_type` ENUM('CRON', 'MANUAL', 'WEBHOOK', 'EVENT') DEFAULT 'CRON',
    `status` ENUM('Pending', 'Running', 'Success', 'Partial', 'Failed') DEFAULT 'Pending',
    `items_discovered` INT UNSIGNED DEFAULT 0,
    `items_processed` INT UNSIGNED DEFAULT 0,
    `new_jobs_found` INT UNSIGNED DEFAULT 0,
    `updated_jobs_found` INT UNSIGNED DEFAULT 0,
    `duplicates_detected` INT UNSIGNED DEFAULT 0,
    `errors_count` INT UNSIGNED DEFAULT 0,
    `duration_seconds` DECIMAL(8,2) DEFAULT 0.00,
    `log_summary` TEXT NULL,
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cr_source` FOREIGN KEY (`source_id`) REFERENCES `source_registry`(`id`) ON DELETE CASCADE,
    INDEX `idx_cr_status` (`status`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crawl_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `crawl_run_id` BIGINT UNSIGNED NOT NULL,
    `source_id` BIGINT UNSIGNED NOT NULL,
    `discovered_url` VARCHAR(1024) NOT NULL,
    `url_hash` CHAR(64) NOT NULL,
    `document_type` ENUM('HTML', 'PDF', 'RSS_ITEM', 'API_OBJECT', 'IMAGE', 'OTHER') NOT NULL DEFAULT 'HTML',
    `document_hash` CHAR(64) NULL,
    `classification` ENUM('JOB', 'EXAM', 'ADMIT_CARD', 'ANSWER_KEY', 'RESULT', 'CUTOFF', 'CORRIGENDUM', 'SYLLABUS', 'NOTICE', 'IRRELEVANT') DEFAULT 'NOTICE',
    `processing_status` ENUM('Pending', 'Extracted', 'Verified', 'Duplicated', 'Failed', 'Skipped') DEFAULT 'Pending',
    `error_details` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ci_run` FOREIGN KEY (`crawl_run_id`) REFERENCES `crawl_runs`(`id`) ON DELETE CASCADE,
    INDEX `idx_ci_hash` (`url_hash`),
    INDEX `idx_ci_doc_hash` (`document_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. INTERNAL LINKS & REDIRECTS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `internal_links` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_entity_type` ENUM('Job', 'Exam', 'Article', 'Page') NOT NULL,
    `source_entity_id` BIGINT UNSIGNED NOT NULL,
    `target_entity_type` ENUM('Job', 'Exam', 'Article', 'Page') NOT NULL,
    `target_entity_id` BIGINT UNSIGNED NOT NULL,
    `anchor_text` VARCHAR(255) NOT NULL,
    `target_url` VARCHAR(512) NOT NULL,
    `link_type` ENUM('Contextual', 'Related_Widget', 'Breadcrumb', 'Footer', 'Header') DEFAULT 'Contextual',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_il_source` (`source_entity_type`, `source_entity_id`),
    INDEX `idx_il_target` (`target_entity_type`, `target_entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `url_redirects` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_path` VARCHAR(512) NOT NULL,
    `target_path` VARCHAR(512) NOT NULL,
    `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    `hit_count` INT UNSIGNED DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_redirect_source` (`source_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
