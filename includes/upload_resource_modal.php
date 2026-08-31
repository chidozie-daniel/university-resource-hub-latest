<?php
$c = $course ?? null;
$mods = $uploadModules ?? [];
if (!$c): return; endif;
?>
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title font-display fw-bold">Upload Learning Resource</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Module</label>
        <select class="form-select mb-3">
          <?php foreach ($mods as $m): ?>
            <option><?= htmlspecialchars($m['title']) ?></option>
          <?php endforeach; ?>
        </select>
        <label class="form-label">Resource Type</label>
        <select class="form-select mb-3"><option>PDF</option><option>Document</option><option>Presentation</option><option>Video</option><option>Image</option><option>Link</option></select>
        <label class="form-label">Title</label>
        <input class="form-control mb-3" placeholder="e.g. Chapter 5 — Indexing & Query Optimization">
        <label class="form-label">Description (optional)</label>
        <textarea class="form-control mb-3" rows="2" placeholder="Short description for students"></textarea>
        <label class="form-label">File</label>
        <div class="border rounded-3 p-3 text-center" style="border-style:dashed!important;background:#FBFCFD;">
          <i class="bi bi-cloud-arrow-up fs-3 text-faint"></i>
          <p class="fs-sm text-muted-2 mb-0 mt-1">Drag & drop, or <a href="#">browse files</a></p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light-2" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" data-bs-dismiss="modal" onclick="alert('Resource uploaded successfully.')">Upload Resource</button>
      </div>
    </div>
  </div>
</div>