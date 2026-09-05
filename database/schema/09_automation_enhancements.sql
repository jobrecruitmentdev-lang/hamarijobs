-- ============================================================================
-- AUTOMATION ENHANCEMENTS SCHEMA MIGRATION
-- Table definitions for Automation Run History, Hash Caching & Verification Queue
-- ============================================================================

-- 1. Automation Execution Runs & Telemetry Log
CREATE TABLE IF NOT EXISTS automation_runs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    run_uuid VARCHAR(64) NOT NULL UNIQUE,
    stage_name VARCHAR(100) NOT NULL,
    trigger_source ENUM('MANUAL_ADMIN', 'SCHEDULED_DAEMON', 'CLI_OPERATOR') NOT NULL DEFAULT 'MANUAL_ADMIN',
    status ENUM('PENDING', 'RUNNING', 'SUCCESS', 'FAILED') NOT NULL DEFAULT 'PENDING',
    notices_found INT NOT NULL DEFAULT 0,
    new_ingested INT NOT NULL DEFAULT 0,
    skipped_unchanged INT NOT NULL DEFAULT 0,
    quarantined_count INT NOT NULL DEFAULT 0,
    execution_time_seconds DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    log_output LONGTEXT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_runs_status (status),
    INDEX idx_runs_stage (stage_name),
    INDEX idx_runs_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Cryptographic SHA-256 Hash Cache for "What's New" Sections
CREATE TABLE IF NOT EXISTS notice_hash_cache (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    source_domain VARCHAR(255) NOT NULL,
    notice_url VARCHAR(1000) NOT NULL,
    content_sha256 VARCHAR(64) NOT NULL,
    title VARCHAR(500) NULL,
    last_checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_processed TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_source_url (source_domain(100), notice_url(255)),
    INDEX idx_hash (content_sha256)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add review_status & anomaly_flags to recruitments if not present
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'recruitments' 
  AND COLUMN_NAME = 'review_status';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE recruitments ADD COLUMN review_status ENUM(\'VERIFIED\', \'REVIEW_PENDING\', \'REJECTED\') NOT NULL DEFAULT \'VERIFIED\' AFTER status, ADD COLUMN anomaly_flags VARCHAR(500) NULL AFTER review_status;', 
    'SELECT 1;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
