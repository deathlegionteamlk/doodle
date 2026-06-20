<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
    </main>
  </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// Close notif dropdown on outside click
document.addEventListener('click', function(e) {
  const drop = document.getElementById('notifDrop');
  if (drop && !e.target.closest('.topbar-icon-btn') && !e.target.closest('#notifDrop')) {
    drop.classList.remove('open');
  }
});

// Confirm before delete actions
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
  });
});

// Auto-dismiss alerts after 5s (convert to toasts)
(function() {
  const flashes = <?= json_encode($data['flash'] ?? []) ?>;
  flashes.forEach(f => showToast(f.type, f.message));
})();

function showToast(type, message, title) {
  const icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="material-icons">' + (icons[type] || 'info') + '</span>' +
                '<div class="toast-content">' +
                (title ? '<div class="toast-title">' + title + '</div>' : '') +
                '<div>' + message + '</div></div>';
  document.getElementById('toastContainer').appendChild(t);
  setTimeout(() => {
    t.style.transition = 'opacity .3s, transform .3s';
    t.style.opacity = '0';
    t.style.transform = 'translateX(120%)';
    setTimeout(() => t.remove(), 300);
  }, 5000);
}

// Auto-dismiss alerts (legacy, in case any are still rendered inline)
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => {
    a.style.transition = 'opacity .5s';
    a.style.opacity = '0';
    setTimeout(() => a.remove(), 500);
  });
}, 5000);

// Keyboard shortcuts (accessibility)
document.addEventListener('keydown', e => {
  // "/" to focus search
  if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
    e.preventDefault();
    const search = document.querySelector('.topbar-search input');
    if (search) search.focus();
  }
  // Esc to close modals
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
    const drop = document.getElementById('notifDrop');
    if (drop) drop.classList.remove('open');
  }
});
</script>
</body>
</html>
