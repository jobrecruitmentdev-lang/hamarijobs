import os
import sys
import requests
import json
from pathlib import Path
from datetime import datetime
from typing import Dict, Any, List, Optional
import xml.etree.ElementTree as ET
import pymysql

ROOT_DIR = str(Path(__file__).resolve().parent.parent.parent)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from automation.config import settings
from automation.logger import logger

class SitemapAndSEOEngine:
    """
    Comprehensive SEO Engine:
    - Generates dynamic XML Sitemaps (Index, Jobs, Exams, Articles, Organizations).
    - Submits newly published and updated URLs to search engines via the IndexNow protocol.
    """
    
    def __init__(self, public_dir: str = "frontend/public"):
        self.public_dir = os.path.join(ROOT_DIR, public_dir)
        os.makedirs(self.public_dir, exist_ok=True)
        self.base_url = settings.APP_URL.rstrip("/")
        
    def get_db_connection(self):
        try:
            return pymysql.connect(
                host=settings.MYSQL_HOST,
                user=settings.MYSQL_USER,
                password=settings.MYSQL_PASSWORD,
                database=settings.MYSQL_DB,
                cursorclass=pymysql.cursors.DictCursor,
                autocommit=True
            )
        except Exception:
            return None

    def generate_all_sitemaps(self) -> Dict[str, str]:
        """
        Builds sitemap-jobs.xml, sitemap-exams.xml, sitemap-articles.xml, and sitemap-index.xml.
        """
        conn = self.get_db_connection()
        if not conn:
            logger.info("ℹ️ [Sitemap] Local DB offline, skipping local XML sitemap file regeneration.")
            return {}

        cur = conn.cursor()

        # 1. Jobs Sitemap
        cur.execute("SELECT slug, updated_at, created_at FROM recruitments WHERE status = 'Active' ORDER BY updated_at DESC;")
        jobs = cur.fetchall()
        jobs_urls = [{"loc": f"{self.base_url}/jobs/{j['slug']}", "lastmod": (j["updated_at"] or j["created_at"]).strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.9"} for j in jobs]
        # Include primary directory pages and programmatic keyword silos in jobs sitemap
        static_pages = [
            {"loc": f"{self.base_url}/", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "1.0"},
            {"loc": f"{self.base_url}/government-jobs", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.9"},
            {"loc": f"{self.base_url}/admit-cards", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.9"},
            {"loc": f"{self.base_url}/results", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.9"},
            # Programmatic Qualification Silos
            {"loc": f"{self.base_url}/jobs/10th-pass", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            {"loc": f"{self.base_url}/jobs/12th-pass", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            {"loc": f"{self.base_url}/jobs/graduate", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            # Programmatic Department Silos
            {"loc": f"{self.base_url}/jobs/railway", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            {"loc": f"{self.base_url}/jobs/police", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            {"loc": f"{self.base_url}/jobs/bank", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            {"loc": f"{self.base_url}/jobs/defence", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            # Programmatic State Silos
            {"loc": f"{self.base_url}/jobs/uttar-pradesh", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            {"loc": f"{self.base_url}/jobs/bihar", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            {"loc": f"{self.base_url}/jobs/rajasthan", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            {"loc": f"{self.base_url}/jobs/delhi", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"},
            {"loc": f"{self.base_url}/jobs/madhya-pradesh", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.85"}
        ]
        jobs_xml_path = self._write_sitemap_xml("sitemap-jobs.xml", static_pages + jobs_urls)

        # 2. Exams Sitemap (Clean URLs with 200 OK status)
        cur.execute("SELECT slug, updated_at, created_at FROM exams WHERE is_active = 1;")
        exams = cur.fetchall()
        exams_urls = [{"loc": f"{self.base_url}/exams", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.9"}]
        for e in exams:
            lastmod = (e["updated_at"] or e["created_at"]).strftime("%Y-%m-%d")
            exams_urls.append({"loc": f"{self.base_url}/exams/{e['slug']}", "lastmod": lastmod, "changefreq": "weekly", "priority": "0.85"})
        exams_xml_path = self._write_sitemap_xml("sitemap-exams.xml", exams_urls)

        # 3. Articles Sitemap
        cur.execute("SELECT slug, updated_at, published_at FROM articles WHERE status = 'Published' ORDER BY updated_at DESC;")
        articles = cur.fetchall()
        articles_urls = [{"loc": f"{self.base_url}/articles", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.9"}]
        for a in articles:
            articles_urls.append({"loc": f"{self.base_url}/articles/{a['slug']}", "lastmod": (a["updated_at"] or a["published_at"]).strftime("%Y-%m-%d"), "changefreq": "weekly", "priority": "0.8"})
        articles_xml_path = self._write_sitemap_xml("sitemap-articles.xml", articles_urls)

        # 4. Commissions Sitemap
        cur.execute("SELECT slug, updated_at, created_at FROM commissions WHERE is_active = 1;")
        commissions = cur.fetchall()
        commissions_urls = [{"loc": f"{self.base_url}/commissions", "lastmod": datetime.now().strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.9"}]
        for c in commissions:
            commissions_urls.append({"loc": f"{self.base_url}/commissions/{c['slug']}", "lastmod": (c["updated_at"] or c["created_at"]).strftime("%Y-%m-%d"), "changefreq": "weekly", "priority": "0.8"})
        commissions_xml_path = self._write_sitemap_xml("sitemap-commissions.xml", commissions_urls)

        conn.close()

        # 5. Master Sitemap Index
        sitemap_files = [
            f"{self.base_url}/sitemap-jobs.xml",
            f"{self.base_url}/sitemap-exams.xml",
            f"{self.base_url}/sitemap-articles.xml",
            f"{self.base_url}/sitemap-commissions.xml"
        ]
        index_xml_path = self._write_sitemap_index("sitemap-index.xml", sitemap_files)
        # Mirror to sitemap.xml and sitemap_index.xml
        self._write_sitemap_index("sitemap.xml", sitemap_files)
        self._write_sitemap_index("sitemap_index.xml", sitemap_files)

        # Synchronize generated sitemaps across root and backend/public
        import shutil
        sync_dirs = [ROOT_DIR, os.path.join(ROOT_DIR, "backend", "public")]
        for sdir in sync_dirs:
            os.makedirs(sdir, exist_ok=True)
            for sname in ["sitemap.xml", "sitemap-index.xml", "sitemap_index.xml", "sitemap-jobs.xml", "sitemap-exams.xml", "sitemap-articles.xml", "sitemap-commissions.xml"]:
                src_file = os.path.join(self.public_dir, sname)
                if os.path.exists(src_file):
                    shutil.copy2(src_file, os.path.join(sdir, sname))

        logger.info(f"🗺 [SitemapEngine] Generated Sitemaps: {len(jobs_urls)} jobs, {len(exams_urls)} exams, {len(articles_urls)} articles, {len(commissions_urls)} commissions")
        return {
            "index": index_xml_path,
            "jobs": jobs_xml_path,
            "exams": exams_xml_path,
            "articles": articles_xml_path,
            "commissions": commissions_xml_path
        }

    def _write_sitemap_xml(self, filename: str, urls: List[Dict[str, str]]) -> str:
        filepath = os.path.join(self.public_dir, filename)
        ET.register_namespace('', 'http://www.sitemaps.org/schemas/sitemap/0.9')
        root = ET.Element('{http://www.sitemaps.org/schemas/sitemap/0.9}urlset')
        
        for u in urls:
            url_elem = ET.SubElement(root, '{http://www.sitemaps.org/schemas/sitemap/0.9}url')
            loc = ET.SubElement(url_elem, '{http://www.sitemaps.org/schemas/sitemap/0.9}loc')
            loc.text = u["loc"]
            if "lastmod" in u:
                lastmod = ET.SubElement(url_elem, '{http://www.sitemaps.org/schemas/sitemap/0.9}lastmod')
                lastmod.text = u["lastmod"]
            if "changefreq" in u:
                changefreq = ET.SubElement(url_elem, '{http://www.sitemaps.org/schemas/sitemap/0.9}changefreq')
                changefreq.text = u["changefreq"]
            if "priority" in u:
                priority = ET.SubElement(url_elem, '{http://www.sitemaps.org/schemas/sitemap/0.9}priority')
                priority.text = u["priority"]
                
        tree = ET.ElementTree(root)
        tree.write(filepath, encoding="utf-8", xml_declaration=True)
        return filepath

    def _write_sitemap_index(self, filename: str, sitemap_urls: List[str]) -> str:
        filepath = os.path.join(self.public_dir, filename)
        ET.register_namespace('', 'http://www.sitemaps.org/schemas/sitemap/0.9')
        root = ET.Element('{http://www.sitemaps.org/schemas/sitemap/0.9}sitemapindex')
        
        now_str = datetime.now().strftime("%Y-%m-%d")
        for s_url in sitemap_urls:
            sitemap_elem = ET.SubElement(root, '{http://www.sitemaps.org/schemas/sitemap/0.9}sitemap')
            loc = ET.SubElement(sitemap_elem, '{http://www.sitemaps.org/schemas/sitemap/0.9}loc')
            loc.text = s_url
            lastmod = ET.SubElement(sitemap_elem, '{http://www.sitemaps.org/schemas/sitemap/0.9}lastmod')
            lastmod.text = now_str
            
        tree = ET.ElementTree(root)
        tree.write(filepath, encoding="utf-8", xml_declaration=True)
        return filepath

    def submit_to_indexnow(self, urls: List[str]) -> bool:
        """
        Submits new or updated URLs to Bing & Yandex via the IndexNow API.
        """
        if not urls:
            return True

        key = settings.INDEXNOW_KEY or "c4f8e2a1b9d0e7f3a5b6c8d1e2f4a5b6"
        host = settings.INDEXNOW_HOST or "hamarijobs.com"
        
        payload = {
            "host": host,
            "key": key,
            "keyLocation": f"https://{host}/{key}.txt",
            "urlList": urls[:10000]
        }
        
        try:
            logger.info(f"🚀 [IndexNow] Submitting {len(urls)} URLs to Bing IndexNow API...")
            res = requests.post("https://api.indexnow.org/indexnow", json=payload, headers={"Content-Type": "application/json"}, timeout=15)
            if res.status_code in (200, 202):
                logger.info(f"✅ [IndexNow] Successfully submitted {len(urls)} URLs to IndexNow protocol.")
                return True
            else:
                logger.warning(f"⚠️ [IndexNow] Response HTTP {res.status_code}: {res.text}")
                return False
        except Exception as e:
            logger.warning(f"⚠️ [IndexNow] Submission failed: {e}")
            return False

    def submit_to_google_indexing(self, urls: List[str]) -> bool:
        """
        Dispatches instant crawl notifications to Google Search via Google Indexing API.
        Requires service account JSON configured in GOOGLE_SERVICE_ACCOUNT_JSON or storage/.
        """
        if not urls:
            return True

        sa_path = os.getenv("GOOGLE_SERVICE_ACCOUNT_JSON", os.path.join(ROOT_DIR, "storage", "google_service_account.json"))
        if not os.path.exists(sa_path):
            logger.info("ℹ️ [GoogleIndexing] Engine Ready. Place 'google_service_account.json' in storage/ to dispatch direct Googlebot pings.")
            return False

        try:
            logger.info(f"🚀 [GoogleIndexing] Submitting {len(urls)} URLs to Google Search Indexing API...")
            # If google-auth is installed, run OAuth2 JWT assertion
            from google.oauth2 import service_account
            from googleapiclient.discovery import build

            credentials = service_account.Credentials.from_service_account_file(
                sa_path,
                scopes=["https://www.googleapis.com/auth/indexing"]
            )
            service = build("indexing", "v3", credentials=credentials)

            for u in urls[:100]:
                content = {"url": u, "type": "URL_UPDATED"}
                service.urlNotifications().publish(body=content).execute()
            
            logger.info(f"✅ [GoogleIndexing] Successfully submitted {len(urls)} URLs to Google Indexing API.")
            return True
        except ImportError:
            logger.info("ℹ️ [GoogleIndexing] 'google-auth' package optional. Install via requirements if Google service account is provided.")
            return False
        except Exception as e:
            logger.warning(f"⚠️ [GoogleIndexing] Dispatch notification: {e}")
            return False

if __name__ == "__main__":
    seo_engine = SitemapAndSEOEngine()
    results = seo_engine.generate_all_sitemaps()
    print("Sitemaps generated:", results)
