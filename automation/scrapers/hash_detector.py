import hashlib
import pymysql
from typing import Optional
from automation.config import settings
from automation.logger import logger

class NoticeHashDetector:
    """
    Cryptographic SHA-256 Hash Detector.
    Tracks official notice releases, prevents redundant AI extraction calls,
    and accurately detects corrigendums and revisions.
    """

    def __init__(self):
        self.db_config = {
            'host': settings.MYSQL_HOST,
            'user': settings.MYSQL_USER,
            'password': settings.MYSQL_PASSWORD,
            'database': settings.MYSQL_DB,
            'cursorclass': pymysql.cursors.DictCursor,
            'autocommit': True
        }

    def get_db(self):
        return pymysql.connect(**self.db_config)

    @staticmethod
    def calculate_sha256(content: str) -> str:
        """Calculates SHA-256 hash of text or binary string."""
        if isinstance(content, str):
            content = content.encode('utf-8', errors='ignore')
        return hashlib.sha256(content).hexdigest()

    def has_content_changed(self, source_domain: str, notice_url: str, current_hash: str) -> bool:
        """
        Checks if the notice is brand new or its content has changed.
        Returns True if new/modified (requires ingestion), False if already processed.
        """
        conn = self.get_db()
        try:
            with conn.cursor() as cur:
                cur.execute(
                    "SELECT content_sha256 FROM notice_hash_cache WHERE source_domain = %s AND notice_url = %s LIMIT 1;",
                    (source_domain, notice_url)
                )
                row = cur.fetchone()
                if not row:
                    # Brand new notice!
                    return True
                
                # Check if cryptographic hash is different (corrigendum / amendment)
                if row['content_sha256'] != current_hash:
                    logger.info(f"🔄 [HashDetector] Content changed for {notice_url}! Corrigendum detected.")
                    return True

                return False
        finally:
            conn.close()

    def record_notice_hash(self, source_domain: str, notice_url: str, current_hash: str, title: Optional[str] = None) -> None:
        """
        Stores or refreshes notice hash in the cache table.
        """
        conn = self.get_db()
        try:
            with conn.cursor() as cur:
                cur.execute("""
                    INSERT INTO notice_hash_cache (source_domain, notice_url, content_sha256, title, last_checked_at, is_processed)
                    VALUES (%s, %s, %s, %s, NOW(), 1)
                    ON DUPLICATE KEY UPDATE 
                        content_sha256 = VALUES(content_sha256),
                        title = VALUES(title),
                        last_checked_at = NOW(),
                        is_processed = 1;
                """, (source_domain, notice_url, current_hash, title or 'Official Notification'))
        finally:
            conn.close()
