// ============================================================================
// GOVERNMENT RECRUITMENT INTELLIGENCE PLATFORM — CLIENT SCRIPTS
// Theme: Red & White Ultra-Clean Light Mode
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
  initMobileDrawer();
  initHeroCarousel();
  initSearch();
  initTabSwitchers();
  initFilterAccordions();
  initAdminControlCenter();
  initBookmarks();
});

// 0. Mobile Navigation Drawer Controls
function initMobileDrawer() {
  const hamburgerBtn = document.getElementById('navHamburgerBtn');
  const closeBtn = document.getElementById('mobileDrawerCloseBtn');
  const drawer = document.getElementById('mobileDrawer');
  const backdrop = document.getElementById('mobileDrawerBackdrop');

  if (!drawer) return;

  function openDrawer() {
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    if (backdrop) backdrop.classList.add('is-active');
    document.body.classList.add('drawer-locked');
    if (hamburgerBtn) hamburgerBtn.classList.add('is-active');
  }

  function closeDrawer() {
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    if (backdrop) backdrop.classList.remove('is-active');
    document.body.classList.remove('drawer-locked');
    if (hamburgerBtn) hamburgerBtn.classList.remove('is-active');
  }

  if (hamburgerBtn) hamburgerBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (drawer.classList.contains('is-open')) {
      closeDrawer();
    } else {
      openDrawer();
    }
  });

  if (closeBtn) closeBtn.addEventListener('click', (e) => {
    e.preventDefault();
    closeDrawer();
  });

  if (backdrop) backdrop.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
      closeDrawer();
    }
  });
}

// 1. Interactive Hero Carousel (Auto-advancing with controls & touch swipe)
function initHeroCarousel() {
  const container = document.querySelector('.carousel-container');
  const slidesWrapper = document.querySelector('.carousel-slides');
  const slides = document.querySelectorAll('.carousel-slide');
  const prevBtn = document.querySelector('.carousel-arrow.prev');
  const nextBtn = document.querySelector('.carousel-arrow.next');

  if (!slidesWrapper || slides.length <= 1) return;

  let currentIdx = 0;
  let timer = null;
  const totalSlides = slides.length;

  function goToSlide(idx) {
    currentIdx = (idx + totalSlides) % totalSlides;
    slidesWrapper.style.transform = `translateX(-${currentIdx * 100}%)`;
  }

  function startAutoPlay() {
    stopAutoPlay();
    timer = setInterval(() => {
      goToSlide(currentIdx + 1);
    }, 6500);
  }

  function stopAutoPlay() {
    if (timer) clearInterval(timer);
  }

  if (prevBtn) prevBtn.addEventListener('click', () => { goToSlide(currentIdx - 1); startAutoPlay(); });
  if (nextBtn) nextBtn.addEventListener('click', () => { goToSlide(currentIdx + 1); startAutoPlay(); });

  if (container) {
    container.addEventListener('mouseenter', stopAutoPlay);
    container.addEventListener('mouseleave', startAutoPlay);

    // Touch Swipe Gestures
    let touchStartX = 0;
    let touchEndX = 0;
    const swipeThreshold = 45;

    container.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
      stopAutoPlay();
    }, { passive: true });

    container.addEventListener('touchend', (e) => {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
      startAutoPlay();
    }, { passive: true });

    function handleSwipe() {
      const diff = touchEndX - touchStartX;
      if (Math.abs(diff) > swipeThreshold) {
        if (diff < 0) {
          // Swipe Left -> Next
          goToSlide(currentIdx + 1);
        } else {
          // Swipe Right -> Prev
          goToSlide(currentIdx - 1);
        }
      }
    }
  }

  startAutoPlay();
}

// Mobile Filter Accordion Toggles
function initFilterAccordions() {
  const toggleBtns = document.querySelectorAll('.filter-toggle-btn');
  toggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target') || 'filterCollapsible';
      const panel = document.getElementById(targetId);
      if (panel) {
        panel.classList.toggle('is-open');
        btn.classList.toggle('is-active');
      }
    });
  });
}

// 2. Global Search Input
function initSearch() {
  const searchInput = document.getElementById('globalSearchInput');
  if (!searchInput) return;

  searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      const q = encodeURIComponent(searchInput.value.trim());
      if (q) window.location.href = `/government-jobs?q=${q}`;
    }
  });
}

// 3. Tab Switchers (Exam Hubs & Detail Pages)
function initTabSwitchers() {
  const tabButtons = document.querySelectorAll('.tab-btn');
  if (!tabButtons.length) return;

  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      
      tabButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
        if (pane.id === targetId) {
          pane.classList.add('active');
        }
      });
    });
  });
}

// 4. Admin Control Center Automation Dispatcher
function initAdminControlCenter() {
  const triggerButtons = document.querySelectorAll('.automation-trigger-btn');
  const terminal = document.getElementById('adminTerminalOutput');
  const statusBadge = document.getElementById('automationStatusBadge');

  if (!triggerButtons.length || !terminal) return;

  triggerButtons.forEach(btn => {
    btn.addEventListener('click', async () => {
      const action = btn.getAttribute('data-action');
      const actionName = btn.innerText.trim();

      triggerButtons.forEach(b => b.disabled = true);
      btn.style.opacity = '0.75';
      if (statusBadge) {
        statusBadge.innerText = `RUNNING: ${actionName}...`;
        statusBadge.style.color = '#d97706';
      }

      appendTerminalLog(`\n>>> [${new Date().toLocaleTimeString()}] DISPATCHING STAGE: ${actionName}...`);

      try {
        const response = await fetch(`/api/v1/admin/trigger?action=${action}`, {
          method: 'POST',
          headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();

        if (result.success) {
          appendTerminalLog(`\n[SUCCESS] Stage finished with returncode 0:\n${result.output}`);
          if (statusBadge) {
            statusBadge.innerText = 'STATUS: IDLE (LAST RUN: SUCCESSFUL)';
            statusBadge.style.color = '#059669';
          }
        } else {
          appendTerminalLog(`\n[ERROR] Worker failed:\n${result.output}`);
          if (statusBadge) {
            statusBadge.innerText = 'STATUS: STAGE FAILED';
            statusBadge.style.color = '#dc2626';
          }
        }
      } catch (err) {
        appendTerminalLog(`\n[EXCEPTION] Network / Server error: ${err.message}`);
      } finally {
        triggerButtons.forEach(b => b.disabled = false);
        btn.style.opacity = '1';
      }
    });
  });

  function appendTerminalLog(text) {
    terminal.innerText += `${text}\n`;
    terminal.scrollTop = terminal.scrollHeight;
  }
}

// 5. Bookmarks Storage
function initBookmarks() {
  window.toggleBookmark = function(jobId, jobTitle) {
    const saved = JSON.parse(localStorage.getItem('gov_job_bookmarks') || '[]');
    const exists = saved.some(item => item.id === jobId);
    
    let updated;
    if (exists) {
      updated = saved.filter(item => item.id !== jobId);
      alert(`Removed "${jobTitle}" from your saved bookmarks.`);
    } else {
      updated = [...saved, { id: jobId, title: jobTitle, savedAt: new Date().toISOString() }];
      alert(`Saved "${jobTitle}" to your bookmarks.`);
    }
    localStorage.setItem('gov_job_bookmarks', JSON.stringify(updated));
  };
}
