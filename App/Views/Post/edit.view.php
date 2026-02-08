<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var \App\Models\Post $post */
?>

<div class="container mt-3">
    <h3>Edit post</h3>

    <?php if (!isset($post)): ?>
        <div class="alert alert-danger">Post not found.</div>
    <?php else: ?>

    <form method="post" action="<?= $link->url('post.update') ?>" enctype="multipart/form-data" id="editPostForm" novalidate>
        <input type="hidden" name="id" value="<?= htmlspecialchars($post->getId()) ?>">

        <div class="mb-3">
            <label for="name" class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($post->getTitle() ?? '') ?>" required maxlength="255" minlength="1">
            <div class="invalid-feedback">Title is required (1-255 characters)</div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" id="description" name="description" rows="5" required minlength="1" maxlength="5000"><?= htmlspecialchars($post->getDescription() ?? '') ?></textarea>
            <div class="invalid-feedback">Description is required (1-5000 characters)</div>
            <small class="form-text text-muted">Max 5000 characters</small>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Image (optional)</label>
            <input class="form-control" type="file" id="image" name="image" accept="image/*">
            <div class="invalid-feedback">Please select a valid image file</div>
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

<script src="/js/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof setupEditPostForm === 'function') setupEditPostForm();

    // Form validation - only on submit
    const form = document.getElementById('editPostForm');
    const titleInput = document.getElementById('name');
    const descInput = document.getElementById('description');
    const imageInput = document.getElementById('image');

    // Image preview - no validation blocking
    imageInput.addEventListener('change', function(ev){
        const f = ev.target.files && ev.target.files[0];
        if (!f){
            document.getElementById('imagePreviewContainer').style.display='none';
            if (document.getElementById('currentImageContainer')) {
                document.getElementById('currentImageContainer').style.display='block';
            }
            return;
        }
        if (!f.type.startsWith('image/')){
            document.getElementById('imagePreviewContainer').style.display='none';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e){
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreviewContainer').style.display = 'block';
            if (document.getElementById('currentImageContainer')) {
                document.getElementById('currentImageContainer').style.display = 'none';
            }
        };
        reader.readAsDataURL(f);
    });

    form.addEventListener('submit', function(ev) {
        const title = titleInput.value.trim();
        const desc = descInput.value.trim();

        // Validate all fields
        let hasErrors = false;

        if (!title || title.length > 255) {
            titleInput.classList.add('is-invalid');
            hasErrors = true;
        } else {
            titleInput.classList.remove('is-invalid');
        }

        if (!desc || desc.length > 5000) {
            descInput.classList.add('is-invalid');
            hasErrors = true;
        } else {
            descInput.classList.remove('is-invalid');
        }

        if (hasErrors) {
            ev.preventDefault();
            ev.stopPropagation();
        }
    });
});
</script>


