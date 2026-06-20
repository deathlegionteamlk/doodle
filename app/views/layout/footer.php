<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
    </main>
  </div>
</div>

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

// Auto-dismiss alerts after 5s
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => {
    a.style.transition = 'opacity .5s';
    a.style.opacity = '0';
    setTimeout(() => a.remove(), 500);
  });
}, 5000);
</script>
</body>
</html>
