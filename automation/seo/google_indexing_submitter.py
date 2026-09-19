import os
import sys
import json
import re
from pathlib import Path
from typing import List

ROOT_DIR = str(Path(__file__).resolve().parent.parent.parent)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from automation.logger import logger

def get_job_urls() -> List[str]:
    """
    Extracts all canonical job URLs from the generated sitemap-jobs.xml.
    """
    sitemap_path = os.path.join(ROOT_DIR, "frontend", "public", "sitemap-jobs.xml")
    if not os.path.exists(sitemap_path):
        sitemap_path = os.path.join(ROOT_DIR, "sitemap-jobs.xml")

    if not os.path.exists(sitemap_path):
        logger.error(f"Sitemap file not found at {sitemap_path}")
        return []

    with open(sitemap_path, "r", encoding="utf-8") as f:
        content = f.read()

    # Extract all /jobs/ URLs
    urls = re.findall(r'<loc>(https://hamarijobs\.com/jobs/[^<]+)</loc>', content)
    # Also include the main directory URL
    urls.insert(0, "https://hamarijobs.com/government-jobs")
    return list(dict.fromkeys(urls))

def submit_urls_to_google(sa_path: str = None) -> bool:
    """
    Submits URLs to Google Web Search Indexing API.
    """
    if sa_path is None:
        sa_path = os.path.join(ROOT_DIR, "storage", "google_service_account.json")

    urls = get_job_urls()
    if not urls:
        print("❌ No job URLs found to submit.")
        return False

    print(f"\n========================================================")
    print(f"🚀 HAMARIJOBS — GOOGLE SEARCH INDEXING ENGINE")
    print(f"========================================================")
    print(f"Total Target URLs to index: {len(urls)}")

    if not os.path.exists(sa_path):
        print(f"\n⚠️ Service Account JSON key not found at:")
        print(f"   {sa_path}")
        print(f"\n👉 Quick Setup Instructions:")
        print(f"   1. Go to Google Cloud Console (https://console.cloud.google.com/)")
        print(f"   2. Enable 'Web Search Indexing API'")
        print(f"   3. Create a Service Account (e.g. indexing-bot@...) and create a JSON Key")
        print(f"   4. Place the downloaded key file as: storage/google_service_account.json")
        print(f"   5. Go to Google Search Console -> Settings -> Users & Permissions -> Add User")
        print(f"   6. Paste Service Account email and grant permission: OWNER")
        print(f"   7. Re-run this script: python automation/seo/google_indexing_submitter.py\n")
        return False

    try:
        from google.oauth2 import service_account
        from googleapiclient.discovery import build

        credentials = service_account.Credentials.from_service_account_file(
            sa_path,
            scopes=["https://www.googleapis.com/auth/indexing"]
        )
        service = build("indexing", "v3", credentials=credentials)

        success_count = 0
        error_count = 0

        print(f"Authenticating with service account: {credentials.service_account_email}...")

        for idx, url in enumerate(urls, 1):
            try:
                body = {
                    "url": url,
                    "type": "URL_UPDATED"
                }
                response = service.urlNotifications().publish(body=body).execute()
                notify_meta = response.get("urlNotificationMetadata", {}).get("latestUpdate", {})
                notify_time = notify_meta.get("notifyTime", "Just now")
                print(f"[{idx}/{len(urls)}] ✅ Dispatched Googlebot Ping: {url}")
                print(f"           Response: URL_UPDATED at {notify_time}")
                success_count += 1
            except Exception as e:
                print(f"[{idx}/{len(urls)}] ⚠️ Failed: {url} -> {e}")
                error_count += 1

        print(f"\n========================================================")
        print(f"🎯 Execution Summary: {success_count} Dispatched, {error_count} Failed")
        print(f"========================================================\n")
        return success_count > 0

    except Exception as e:
        print(f"\n❌ Google Indexing API Error: {e}")
        return False

if __name__ == "__main__":
    submit_urls_to_google()
