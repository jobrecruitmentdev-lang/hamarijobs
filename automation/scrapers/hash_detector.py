import os
import json
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
        try:
            return pymysql.connect(**self.db_config)
        except Exception:
            return None

    def _get_json_cache_path(self) -> str:
        base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
        storage_dir = os.path.join(base_dir, "storage")
        os.makedirs(storage_dir, exist_ok=True)
        return os.path.join(storage_dir, "notice_hashes.json")

    def _read_json_cache(self) -> dict:
        path = self._get_json_cache_path()
        if os.path.exists(path):
            try:
                with open(path, "r", encoding="utf-8") as f:
                    return json.load(f)
            except Exception:
                pass
        return {}

    def _write_json_cache(self, cache: dict) -> None:
        path = self._get_json_cache_path()
        try:
            with open(path, "w", encoding="utf-8") as f:
                json.dump(cache, f, indent=2)
        except Exception:
            pass

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
        if conn:
            try:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT content_sha256 FROM notice_hash_cache WHERE source_domain = %s AND notice_url = %s LIMIT 1;",
                        (source_domain, notice_url)
                    )
                    row = cur.fetchone()
                    if not row:
                        return True
                    if row['content_sha256'] != current_hash:
                        logger.info(f"🔄 [HashDetector] Content changed for {notice_url}! Corrigendum detected.")
                        return True
                    return False
            finally:
                conn.close()
        
        # Fallback to local JSON hash cache if MySQL is offline
        cache = self._read_json_cache()
        key = f"{source_domain}|{notice_url}"
        cached_hash = cache.get(key)
        if cached_hash != current_hash:
            return True
        return False

    def record_notice_hash(self, source_domain: str, notice_url: str, current_hash: str, title: Optional[str] = None) -> None:
        """
        Stores or refreshes notice hash in both MySQL and local JSON cache.
        """
        conn = self.get_db()
        if conn:
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

        # Always update local JSON cache
        cache = self._read_json_cache()
        key = f"{source_domain}|{notice_url}"
        cache[key] = current_hash
        self._write_json_cache(cache)
