# HamariJobs - Government Recruitment & Jobs Intelligence Platform

HamariJobs is an automated recruitment intelligence and job aggregation platform designed to crawl, parse, normalize, and publish government job notifications, admit cards, results, and answer keys across central and state government portals.

## 🚀 Features

- **Automated Crawlers**: Adapters and engines for major recruitment boards (SSC, UPSC, State PSCs, Railway, Banking, Defense, etc.).
- **Smart Extraction & AI**: OCR for notifications and LLM-assisted information extraction for structured vacancy details.
- **Modern Backend**: FastAPI-powered REST APIs with modular architecture for job seekers and administrative management.
- **SEO & Indexing**: Real-time IndexNow integration and search engine indexing automation.
- **Dynamic Frontend**: Fast, responsive, accessible portal for job discovery, filtering, alerts, and notifications.

## 🛠️ Tech Stack

- **Backend**: Python 3.10+, FastAPI, SQLAlchemy, Uvicorn, Pydantic
- **Database**: MySQL / SQLite (Development)
- **Queue & Caching**: Redis
- **Scraping & Automation**: Playwright, BeautifulSoup4, Requests, APScheduler
- **AI & OCR**: Google Gemini API, Tesseract OCR

## 📦 Getting Started

### 1. Prerequisites
- Python 3.10 or higher
- Redis (optional for queue operations)
- Tesseract OCR (for document parsing)

### 2. Installation

Clone the repository:
```bash
git clone https://github.com/jobrecruitmentdev-lang/hamarijobs.git
cd hamarijobs
```

Create and activate a virtual environment:
```bash
python -m venv venv
# On Windows:
.\venv\Scripts\activate
# On Linux/macOS:
source venv/bin/activate
```

Install dependencies:
```bash
pip install -r requirements.txt
```

### 3. Configuration

Copy the example environment file and configure your credentials:
```bash
cp .env.example .env
```

Edit `.env` to configure your database connection, API keys (e.g. Gemini), and server ports.

### 4. Running the Platform

**Start the Backend API Server:**
```bash
python backend/run.py
```
Or run `start_server.bat` on Windows.

**Run the Crawler & Automation Suite:**
```bash
python automation/engine/crawler.py
```
Or run `run_gov_crawler.bat` on Windows.

## 📄 License
All rights reserved. Proprietary software.
