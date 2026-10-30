import os
import sys
import json
import re
import time
from pathlib import Path
from typing import List

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

ROOT_DIR = str(Path(__file__).resolve().parent.parent.parent)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from automation.logger import logger

def get_job_urls() -> List[str]:
    """
    Extracts all clean canonical URLs across jobs, exams, articles, commissions, and silos,
    strictly normalizing duplicated slug patterns (e.g. -2026-2026 -> -2026).
    """
    raw_urls = []
    all_urls_path = os.path.join(ROOT_DIR, "scratch", "all_urls.txt")
    if os.path.exists(all_urls_path):
        with open(all_urls_path, "r", encoding="utf-8") as f:
            raw_urls = [line.strip() for line in f if line.strip() and line.strip().startswith("https://")]

    if not raw_urls:
        raw_urls = ["https://hamarijobs.com/", "https://hamarijobs.com/government-jobs", "https://hamarijobs.com/sitemap"]
        for sm_name in ["sitemap-jobs.xml", "sitemap-exams.xml", "sitemap-articles.xml", "sitemap-commissions.xml"]:
            sm_path = os.path.join(ROOT_DIR, "frontend", "public", sm_name)
            if not os.path.exists(sm_path):
                sm_path = os.path.join(ROOT_DIR, sm_name)
            if os.path.exists(sm_path):
                with open(sm_path, "r", encoding="utf-8") as f:
                    found = re.findall(r'<loc>(https://hamarijobs\.com/[^<]+)</loc>', f.read())
                    raw_urls.extend(found)

    # Normalize URLs: replace duplicate year segments (e.g. -2026-2026) with clean single year
    clean_urls = []
    for u in raw_urls:
        normalized = re.sub(r'-(202\d)-(202\d)', r'-\1', u)
        clean_urls.append(normalized)

    # Prioritize JobPosting URLs first to strictly align with Google Web Search Indexing guidelines
    job_urls = [u for u in clean_urls if '/jobs/' in u and not any(u.endswith(s) for s in ['10th-pass', '12th-pass', 'graduate', 'railway', 'police', 'bank', 'defence', 'uttar-pradesh', 'bihar', 'rajasthan', 'delhi', 'madhya-pradesh'])]
    other_urls = [u for u in clean_urls if u not in job_urls]

    sorted_urls = list(dict.fromkeys(job_urls + other_urls))
    return sorted_urls

def get_service_account_paths() -> List[str]:
    """
    Discovers all valid Google service account JSON files in storage/ for multi-key quota pooling.
    """
    keys = []
    candidates = [
        os.path.join(ROOT_DIR, "storage", "google_service_account.json"),
        os.path.join(ROOT_DIR, "storage", "google_service_account_atoz.json"),
        os.path.join(ROOT_DIR, "storage", "google_service_account_cosmic.json")
    ]
    seen_emails = set()
    for c in candidates:
        if os.path.exists(c):
            try:
                with open(c, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    email = data.get('client_email')
                    if email and email not in seen_emails:
                        seen_emails.add(email)
                        keys.append(c)
            except Exception:
                pass
    return keys

def submit_urls_to_google(sa_path: str = None) -> bool:
    """
    Submits URLs to Google Web Search Indexing API with multi-key failover & quota rotation.
    """
    from google.oauth2 import service_account
    from googleapiclient.discovery import build

    urls = get_job_urls()
    if not urls:
        print("❌ No job URLs found to submit.")
        return False

    sa_paths = [sa_path] if sa_path else get_service_account_paths()
    if not sa_paths:
        print("\n⚠️ No valid Service Account JSON keys found in storage/.")
        return False

    print(f"\n========================================================")
    print(f"🚀 HAMARIJOBS — GOOGLE SEARCH INDEXING ENGINE (MULTI-KEY)")
    print(f"========================================================")
    print(f"Total Target URLs to index: {len(urls)}")
    print(f"Active Service Account Keys: {len(sa_paths)}")

    # Initialize services
    services = []
    for p in sa_paths:
        try:
            creds = service_account.Credentials.from_service_account_file(
                p, scopes=["https://www.googleapis.com/auth/indexing"]
            )
            svc = build("indexing", "v3", credentials=creds)
            services.append({"email": creds.service_account_email, "service": svc, "path": p, "exhausted": False})
            print(f"  • Loaded Service Account: {creds.service_account_email}")
        except Exception as e:
            print(f"  ⚠️ Could not load {p}: {e}")

    if not services:
        print("❌ No usable Google Indexing API clients.")
        return False

    current_idx = 0
    success_count = 0
    error_count = 0

    for idx, url in enumerate(urls, 1):
        if not url.startswith("https://hamarijobs.com"):
            print(f"[{idx}/{len(urls)}] ⚠️ Skipping non-hamarijobs URL: {url}")
            continue

        dispatched = False
        attempts = 0
        while attempts < len(services) and not dispatched:
            svc_info = services[current_idx]
            if svc_info["exhausted"]:
                current_idx = (current_idx + 1) % len(services)
                attempts += 1
                continue

            try:
                body = {"url": url, "type": "URL_UPDATED"}
                response = svc_info["service"].urlNotifications().publish(body=body).execute()
                notify_meta = response.get("urlNotificationMetadata", {}).get("latestUpdate", {})
                notify_time = notify_meta.get("notifyTime", "Just now")
                print(f"[{idx}/{len(urls)}] ✅ Dispatched Googlebot Ping ({svc_info['email'].split('@')[0]}): {url}")
                print(f"           Response: URL_UPDATED at {notify_time}")
                success_count += 1
                dispatched = True
                time.sleep(0.15)
            except Exception as e:
                err_str = str(e)
                if "Quota exceeded" in err_str or "RATE_LIMIT_EXCEEDED" in err_str:
                    print(f"[{idx}/{len(urls)}] ⚠️ Key quota exhausted for {svc_info['email']}. Rotating to next key...")
                    svc_info["exhausted"] = True
                    current_idx = (current_idx + 1) % len(services)
                    attempts += 1
                else:
                    print(f"[{idx}/{len(urls)}] ⚠️ Failed: {url} -> {e}")
                    error_count += 1
                    dispatched = True  # Move to next URL

        if not dispatched:
            print(f"[{idx}/{len(urls)}] ⛔ All service account keys exhausted daily quota for: {url}")
            error_count += 1

    print(f"\n========================================================")
    print(f"🎯 Execution Summary: {success_count} Dispatched, {error_count} Failed")
    print(f"========================================================\n")
    return success_count > 0

if __name__ == "__main__":
    submit_urls_to_google()
