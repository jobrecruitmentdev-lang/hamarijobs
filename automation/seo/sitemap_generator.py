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
        return pymysql.connect(
            host=settings.MYSQL_HOST,
            user=settings.MYSQL_USER,
            password=settings.MYSQL_PASSWORD,
            database=settings.MYSQL_DB,
            cursorclass=pymysql.cursors.DictCursor,
            autocommit=True
        )

    def generate_all_sitemaps(self) -> Dict[str, str]:
        """
        Builds sitemap-jobs.xml, sitemap-exams.xml, sitemap-articles.xml, and sitemap-index.xml.
        """
        conn = self.get_db_connection()
        cur = conn.cursor()

        # 1. Jobs Sitemap
        cur.execute("SELECT slug, updated_at, created_at FROM recruitments WHERE status = 'Active' ORDER BY updated_at DESC;")
        jobs = cur.fetchall()
        jobs_urls = [{"loc": f"{self.base_url}/jobs/{j['slug']}", "lastmod": (j["updated_at"] or j["created_at"]).strftime("%Y-%m-%d"), "changefreq": "daily", "priority": "0.9"} for j in jobs]
        jobs_xml_path = self._write_sitemap_xml("sitemap-jobs.xml", jobs_urls)

        # 2. Exams Sitemap
        cur.execute("SELECT slug, updated_at, created_at FROM exams WHERE is_active = 1;")
        exams = cur.fetchall()
        exams_urls = []
        for e in exams:
            lastmod = (e["updated_at"] or e["created_at"]).strftime("%Y-%m-%d")
            exams_urls.append({"loc": f"{self.base_url}/exams/{e['slug']}", "lastmod": lastmod, "changefreq": "weekly", "priority": "0.9"})
            exams_urls.append({"loc": f"{self.base_url}/exams/{e['slug']}/syllabus", "lastmod": lastmod, "changefreq": "monthly", "priority": "0.8"})
            exams_urls.append({"loc": f"{self.base_url}/exams/{e['slug']}/exam-pattern", "lastmod": lastmod, "changefreq": "monthly", "priority": "0.8"})
            exams_urls.append({"loc": f"{self.base_url}/exams/{e['slug']}/cutoff", "lastmod": lastmod, "changefreq": "monthly", "priority": "0.8"})
        exams_xml_path = self._write_sitemap_xml("sitemap-exams.xml", exams_urls)

        # 3. Articles Sitemap
        cur.execute("SELECT slug, updated_at, published_at FROM articles WHERE status = 'Published' ORDER BY updated_at DESC;")
        articles = cur.fetchall()
        articles_urls = [{"loc": f"{self.base_url}/articles/{a['slug']}", "lastmod": (a["updated_at"] or a["published_at"]).strftime("%Y-%m-%d"), "changefreq": "weekly", "priority": "0.8"} for a in articles]
        articles_xml_path = self._write_sitemap_xml("sitemap-articles.xml", articles_urls)

        conn.close()

        # 4. Master Sitemap Index
        sitemap_files = [
            f"{self.base_url}/sitemap-jobs.xml",
            f"{self.base_url}/sitemap-exams.xml",
            f"{self.base_url}/sitemap-articles.xml"
        ]
        index_xml_path = self._write_sitemap_index("sitemap-index.xml", sitemap_files)
        # Also mirror to sitemap.xml for root discovery
        self._write_sitemap_index("sitemap.xml", sitemap_files)

        logger.info(f"🗺 [SitemapEngine] Generated Sitemaps: {len(jobs_urls)} jobs, {len(exams_urls)} exams, {len(articles_urls)} articles")
        return {
            "index": index_xml_path,
            "jobs": jobs_xml_path,
            "exams": exams_xml_path,
            "articles": articles_xml_path
        }

    def _write_sitemap_xml(self, filename: str, urls: List[Dict[str, str]]) -> str:
        filepath = os.path.join(self.public_dir, filename)
        root = ET.Element("urlset", xmlns="http://www.sitemaps.org/schemas/sitemap/0.9")
        
        for u in urls:
            url_elem = ET.SubElement(root, "url")
            loc = ET.SubElement(url_elem, "loc")
            loc.text = u["loc"]
            if "lastmod" in u:
                lastmod = ET.SubElement(url_elem, "lastmod")
                lastmod.text = u["lastmod"]
            if "changefreq" in u:
                changefreq = ET.SubElement(url_elem, "changefreq")
                changefreq.text = u["changefreq"]
            if "priority" in u:
                priority = ET.SubElement(url_elem, "priority")
                priority.text = u["priority"]
                
        tree = ET.ElementTree(root)
        tree.write(filepath, encoding="utf-8", xml_declaration=True)
        return filepath

    def _write_sitemap_index(self, filename: str, sitemap_urls: List[str]) -> str:
        filepath = os.path.join(self.public_dir, filename)
        root = ET.Element("sitemapindex", xmlns="http://www.sitemaps.org/schemas/sitemap/0.9")
        
        now_str = datetime.now().strftime("%Y-%m-%d")
        for s_url in sitemap_urls:
            sitemap_elem = ET.SubElement(root, "sitemap")
            loc = ET.SubElement(sitemap_elem, "loc")
            loc.text = s_url
            lastmod = ET.SubElement(sitemap_elem, "lastmod")
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
            logger.error(f"❌ [IndexNow] Submission error: {e}")
            return False

if __name__ == "__main__":
    seo_engine = SitemapAndSEOEngine()
    results = seo_engine.generate_all_sitemaps()
    print("Sitemaps generated:", results)
