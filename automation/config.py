import os
from pathlib import Path
from dotenv import load_dotenv

BASE_DIR = Path(__file__).resolve().parent.parent
load_dotenv(os.path.join(BASE_DIR, ".env"))

class AutomationSettings:
    APP_ENV: str = os.getenv("APP_ENV", "development")
    APP_DEBUG: bool = os.getenv("APP_DEBUG", "true").lower() == "true"
    APP_URL: str = os.getenv("APP_URL", "http://localhost:8000")
    API_V1_STR: str = os.getenv("API_V1_STR", "/api/v1")
    PROJECT_NAME: str = os.getenv("PROJECT_NAME", "Government Recruitment Intelligence Platform")
    
    # Security
    SECRET_KEY: str = os.getenv("SECRET_KEY", "9a7c3b8f2e1d4c5a6b7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c")
    INTERNAL_API_SECRET: str = os.getenv("INTERNAL_API_SECRET", "gov_sec_sync_k9a2b8e4f1c7d3a5e8b0c2d4e6f8a0b2")
    
    # Database
    MYSQL_HOST: str = os.getenv("MYSQL_HOST", "127.0.0.1")
    MYSQL_PORT: int = int(os.getenv("MYSQL_PORT", 3306))
    MYSQL_USER: str = os.getenv("MYSQL_USER", "root")
    MYSQL_PASSWORD: str = os.getenv("MYSQL_PASSWORD", "")
    MYSQL_DB: str = os.getenv("MYSQL_DB", "job_recruitment_ai")
    
    # Redis
    REDIS_HOST: str = os.getenv("REDIS_HOST", "127.0.0.1")
    REDIS_PORT: int = int(os.getenv("REDIS_PORT", 6379))
    REDIS_DB: int = int(os.getenv("REDIS_DB", 0))
    QUEUE_DRIVER: str = os.getenv("QUEUE_DRIVER", "redis")
    
    # AI / LLM
    OPENAI_API_KEY: str = os.getenv("OPENAI_API_KEY", "")
    GEMINI_API_KEY: str = os.getenv("GEMINI_API_KEY", "")
    DEFAULT_LLM_MODEL: str = os.getenv("DEFAULT_LLM_MODEL", "gpt-4o")
    FALLBACK_LLM_MODEL: str = os.getenv("FALLBACK_LLM_MODEL", "gemini-1.5-flash")
    
    # OCR & Storage
    TESSERACT_CMD: str = os.getenv("TESSERACT_CMD", r"C:\Program Files\Tesseract-OCR\tesseract.exe")
    TESSDATA_PREFIX: str = os.getenv("TESSDATA_PREFIX", r"C:\Program Files\Tesseract-OCR\tessdata")
    OCR_LANGUAGES: str = os.getenv("OCR_LANGUAGES", "eng+hin")
    PDF_STORAGE_DIR: str = os.getenv("PDF_STORAGE_DIR", "storage/notifications")
    TEMP_DOWNLOAD_DIR: str = os.getenv("TEMP_DOWNLOAD_DIR", "storage/temp")
    LOGS_DIR: str = "storage/logs"
    
    # Crawler
    USER_AGENT: str = os.getenv("USER_AGENT", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) GovernmentRecruitmentBot/2.0")
    CRAWL_TIMEOUT_SECONDS: int = int(os.getenv("CRAWL_TIMEOUT_SECONDS", 30))
    MAX_CONCURRENT_CRAWLS: int = int(os.getenv("MAX_CONCURRENT_CRAWLS", 5))
    RATE_LIMIT_DELAY_SECONDS: float = float(os.getenv("RATE_LIMIT_DELAY_SECONDS", 2.0))
    
    # SEO & IndexNow
    INDEXNOW_KEY: str = os.getenv("INDEXNOW_KEY", "")
    INDEXNOW_HOST: str = os.getenv("INDEXNOW_HOST", "hamarijobs.com")
    SEARCH_ENGINE_AUTO_SUBMIT: bool = os.getenv("SEARCH_ENGINE_AUTO_SUBMIT", "true").lower() == "true"

    @property
    def SQLALCHEMY_DATABASE_URI(self) -> str:
        return f"mysql+pymysql://{self.MYSQL_USER}:{self.MYSQL_PASSWORD}@{self.MYSQL_HOST}:{self.MYSQL_PORT}/{self.MYSQL_DB}?charset=utf8mb4"

settings = AutomationSettings()

# Ensure storage directories exist
for dir_path in [settings.PDF_STORAGE_DIR, settings.TEMP_DOWNLOAD_DIR, settings.LOGS_DIR]:
    full_path = os.path.join(BASE_DIR, dir_path)
    os.makedirs(full_path, exist_ok=True)
