<?php
/**
 * Closes main / app shell and loads scripts.
 */
?>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function () {
  const sb = document.getElementById('uhSidebar');
  const bd = document.getElementById('sidebarBackdrop');
  sb?.classList.add('show');
  if (bd) {
    bd.classList.remove('d-none');
    bd.classList.add('show', 'd-block');
  }
});
</script>
</body>
</html>
