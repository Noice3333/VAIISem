<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var \App\Models\Post $post */
?>

<div class="container mt-3">
    <h3>Edit post</h3>

    <?php if (!isset($post)): ?>
        <div class="alert alert-danger">Post not found.</div>
    <?php else: ?>

    <form method="post" action="<?= $link->url('post.update') ?>" enctype="multipart/form-data" id="editPostForm">
        <input type="hidden" name="id" value="<?= htmlspecialchars($post->getId()) ?>">

        <div class="mb-3">
            <label for="name" class="form-label">Title</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($post->getTitle() ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($post->getDescription() ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Image (optional)</label>
            <input class="form-control" type="file" id="image" name="image" accept="image/*">
            <?php if ($post->getImage()): ?>
                <div id="currentImageContainer" style="margin-top:8px;">
                    <p style="margin:0 0 6px 0;"><small>Current image:</small></p>
                    <img id="currentImage" src="<?= htmlspecialchars($post->getImage()) ?>" alt="Current image" style="max-width:100%;height:auto;border-radius:6px;" />
                </div>
            <?php endif; ?>
            <div id="imagePreviewContainer" style="margin-top:8px;display:none;">
                <p style="margin:0 0 6px 0;"><small>New preview:</small></p>
                <img id="imagePreview" src="#" alt="Preview" style="max-width:100%;height:auto;border-radius:6px;" />
            </div>
        </div>

        <input type="hidden" id="lat" name="lat" value="<?= htmlspecialchars($post->getLatitude() ?? '') ?>">
        <input type="hidden" id="lng" name="lng" value="<?= htmlspecialchars($post->getLongitude() ?? '') ?>">

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>

            <button type="button" id="deleteBtn" class="btn btn-danger">Delete</button>

            <a class="btn btn-secondary" href="<?= $link->url('home.index') ?>">Cancel</a>
        </div>
    </form>

    <!-- Hidden delete form placed outside of the edit form to avoid nested forms -->
    <form method="post" action="<?= $link->url('post.delete') ?>" id="deleteForm" style="display:none;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($post->getId()) ?>">
    </form>

    <?php endif; ?>

</div>

<script>
(function(){
    // Image preview logic
    const fileInput = document.getElementById('image');
    const preview = document.getElementById('imagePreview');
    const container = document.getElementById('imagePreviewContainer');
    const current = document.getElementById('currentImageContainer');
    if (fileInput) {
        fileInput.addEventListener('change', function(ev){
            const f = ev.target.files && ev.target.files[0];
            if (!f) { if (container) container.style.display = 'none'; if (preview) preview.src = '#'; return; }
            if (!f.type.startsWith('image/')) { if (container) container.style.display='none'; if (preview) preview.src='#'; return; }
            const reader = new FileReader();
            reader.onload = function(e){
                if (preview) preview.src = e.target.result;
                if (container) container.style.display = 'block';
                if (current) current.style.display = 'none';
            };
            reader.readAsDataURL(f);
        });
    }

    // Ensure lat/lng present on submit
    const form = document.getElementById('editPostForm');
    if (form) form.addEventListener('submit', function(ev){
        const lat = document.getElementById('lat').value;
        const lng = document.getElementById('lng').value;
        if (!lat || !lng) {
            ev.preventDefault();
            alert('Please select a location on the map before submitting.');
        }
    });

    // Delete confirmation — submits the hidden delete form
    const deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(){
            if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
})();
</script>
