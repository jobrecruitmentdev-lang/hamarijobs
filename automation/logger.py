import os
import sys
import logging
from logging.handlers import RotatingFileHandler
from automation.config import settings, BASE_DIR

def setup_logger(name: str = "GovIntel", log_file: str = "automation.log", level: int = logging.INFO) -> logging.Logger:
    """
    Configures an enterprise-grade logger with console and rotating file output.
    """
    logger = logging.getLogger(name)
    if logger.handlers:
        return logger

    logger.setLevel(level)

    # Format
    formatter = logging.Formatter(
        fmt="%(asctime)s [%(levelname)s] [%(name)s:%(lineno)d] %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S"
    )

    # Console Handler
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)

    # File Handler
    log_dir = os.path.join(BASE_DIR, settings.LOGS_DIR)
    os.makedirs(log_dir, exist_ok=True)
    file_path = os.path.join(log_dir, log_file)

    file_handler = RotatingFileHandler(
        file_path,
        maxBytes=10 * 1024 * 1024, # 10MB
        backupCount=5,
        encoding="utf-8"
    )
    file_handler.setFormatter(formatter)
    logger.addHandler(file_handler)

    return logger

logger = setup_logger()
