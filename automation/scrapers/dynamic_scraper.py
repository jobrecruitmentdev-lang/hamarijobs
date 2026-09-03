import logging
import asyncio
from typing import Optional
from automation.config import settings
from automation.logger import logger

class DynamicScraper:
    """
    Dynamic web scraper using Playwright for JavaScript-rendered SPAs,
    React/Angular portals (such as modern SSC, SBI Careers, and RRB portals).
    Supports both async and synchronous invocation.
    """
    
    def __init__(self, headless: bool = True):
        self.headless = headless
        
    async def fetch_page_async(self, url: str, wait_for_selector: Optional[str] = None, timeout_ms: int = 30000) -> Optional[str]:
        """
        Fetches fully rendered HTML using Playwright async API.
        """
        try:
            from playwright.async_api import async_playwright
            
            async with async_playwright() as p:
                browser = await p.chromium.launch(
                    headless=self.headless,
                    args=["--no-sandbox", "--disable-setuid-sandbox", "--disable-dev-shm-usage"]
                )
                context = await browser.new_context(
                    user_agent=settings.USER_AGENT,
                    locale="en-IN",
                    viewport={"width": 1280, "height": 800}
                )
                page = await context.new_page()
                
                logger.info(f"[DynamicScraper] Navigating to {url}")
                await page.goto(url, wait_until="domcontentloaded", timeout=timeout_ms)
                
                if wait_for_selector:
                    try:
                        await page.wait_for_selector(wait_for_selector, timeout=10000)
                    except Exception:
                        logger.warning(f"[DynamicScraper] Selector '{wait_for_selector}' not found on {url}, continuing with available DOM.")
                else:
                    # Allow JS framework hydration
                    await page.wait_for_timeout(2500)
                    
                content = await page.content()
                await browser.close()
                return content
                
        except Exception as e:
            logger.error(f"[DynamicScraper] Playwright rendering failed for {url}: {e}")
            return None

    def fetch_page(self, url: str, wait_for_selector: Optional[str] = None) -> Optional[str]:
        """
        Synchronous wrapper for easy execution in sync orchestrator/worker threads.
        """
        try:
            return asyncio.run(self.fetch_page_async(url, wait_for_selector))
        except RuntimeError:
            # Handle cases where an event loop is already running in thread
            loop = asyncio.get_event_loop()
            return loop.run_until_complete(self.fetch_page_async(url, wait_for_selector))
