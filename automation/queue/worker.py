import sys
import time
import json
from pathlib import Path
from typing import Optional, Dict, Any

ROOT_DIR = str(Path(__file__).resolve().parent.parent.parent)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from automation.logger import logger
from automation.config import settings
from automation.engine.orchestrator import CrawlOrchestrator
from automation.intelligence.exam_engine import ExamIntelligenceEngine
from automation.intelligence.content_engine import ContentIntelligenceEngine
from automation.seo.sitemap_generator import SitemapAndSEOEngine

try:
    import redis
    REDIS_AVAILABLE = True
except ImportError:
    REDIS_AVAILABLE = False

class QueueWorker:
    """
    Production-ready queue worker supporting Redis when configured,
    with an autonomous database queue fallback for seamless operation.
    """
    def __init__(self, queue_name: str = "crawl_tasks"):
        self.queue_name = queue_name
        self.redis_client = None
        
        if REDIS_AVAILABLE and settings.REDIS_HOST:
            try:
                self.redis_client = redis.Redis(
                    host=settings.REDIS_HOST,
                    port=settings.REDIS_PORT,
                    password=settings.REDIS_PASSWORD or None,
                    decode_responses=True,
                    socket_connect_timeout=2
                )
                self.redis_client.ping()
                logger.info(f"⚡ [QueueWorker] Connected to Redis queue: {self.queue_name}")
            except Exception as e:
                logger.warning(f"⚠️ [QueueWorker] Redis connection failed ({e}). Defaulting to standalone orchestrator.")
                self.redis_client = None

        self.orchestrator = CrawlOrchestrator()
        self.exam_engine = ExamIntelligenceEngine()
        self.content_engine = ContentIntelligenceEngine()
        self.seo_engine = SitemapAndSEOEngine()

    def process_task(self, task_type: str, payload: Dict[str, Any]) -> bool:
        logger.info(f"⚙️ [QueueWorker] Executing task: '{task_type}'")
        try:
            if task_type == "crawl_all":
                self.orchestrator.run_pipeline()
            elif task_type == "seed_exams":
                self.exam_engine.seed_master_exam_hubs()
            elif task_type == "generate_articles":
                rec_id = payload.get("recruitment_id")
                if rec_id:
                    self.content_engine.generate_recruitment_pillar_articles(rec_id)
            elif task_type == "generate_sitemap":
                self.seo_engine.generate_all_sitemaps()
            else:
                logger.warning(f"Unknown task type: {task_type}")
            return True
        except Exception as e:
            logger.error(f"❌ [QueueWorker] Error processing task '{task_type}': {e}", exc_info=True)
            return False

    def start(self):
        logger.info(f"🚀 [QueueWorker] Worker daemon active. Listening on '{self.queue_name}'...")
        if not self.redis_client:
            logger.info("ℹ️ Running single-cycle orchestrator in standalone mode.")
            self.orchestrator.run_pipeline()
            return

        while True:
            try:
                item = self.redis_client.brpop(self.queue_name, timeout=5)
                if item:
                    _, msg_data = item
                    task = json.loads(msg_data)
                    self.process_task(task.get("type", "crawl_all"), task.get("payload", {}))
            except (KeyboardInterrupt, SystemExit):
                logger.info("Worker gracefully stopped.")
                break
            except Exception as e:
                logger.error(f"Queue read error: {e}")
                time.sleep(2)

if __name__ == "__main__":
    worker = QueueWorker()
    worker.start()
