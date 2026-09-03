      </div><!-- /.admin-container -->
    </main><!-- /.admin-content-scroll -->

  </div><!-- /.admin-main-wrapper -->

</div><!-- /.admin-app-shell -->

<!-- Core Unified Admin Interactive Scripts -->
<script>
// 1. Mobile Sidebar Navigation Drawer Controls
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('adminSidebar');
  const backdrop = document.getElementById('adminSidebarBackdrop');
  const toggleBtn = document.getElementById('adminMobileToggleBtn');
  const closeBtn = document.getElementById('adminSidebarCloseBtn');

  function openSidebar() {
    if (sidebar) sidebar.classList.add('open');
    if (backdrop) backdrop.classList.add('active');
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('active');
  }

  if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
  if (backdrop) backdrop.addEventListener('click', closeSidebar);
});

// 2. Universal Modal System (Esc key & Outside Click Handling)
window.openModal = function(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('active');
    const firstInput = modal.querySelector('input:not([type=hidden]), select, textarea');
    if (firstInput) setTimeout(() => firstInput.focus(), 100);
  }
};

window.closeModal = function(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('active');
  }
};

// Global Esc and Backdrop Dismissal for all Modals
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.admin-modal-overlay.active, .modal-overlay.active').forEach(m => {
      m.classList.remove('active');
    });
  }
});

document.addEventListener('click', (e) => {
  if (e.target.classList.contains('admin-modal-overlay') || e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('active');
  }
});
</script>
<script src="/assets/js/app.js"></script>
</body>
</html>
