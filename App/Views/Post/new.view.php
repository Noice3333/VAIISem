<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var string|null $lat */
/** @var string|null $lng */
/** @var string|null $error */
?>

<div class="container mt-3">
    <h3>Create new post</h3>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $link->url('post.create') ?>" enctype="multipart/form-data" id="newPostForm" novalidate>
        <div class="mb-3">
            <label for="name" class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required maxlength="255" minlength="1">
            <div class="invalid-feedback">Title is required (1-255 characters)</div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" id="description" name="description" rows="5" required minlength="1" maxlength="5000"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            <div class="invalid-feedback">Description is required (1-5000 characters)</div>
            <small class="form-text text-muted">Max 5000 characters</small>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Image (optional)</label>
            <input class="form-control" type="file" id="image" name="image" accept="image/*">
            <div class="invalid-feedback">Please select a valid image file</div>
            <div id="imagePreviewContainer" style="margin-top:8px;display:none;">
                <p style="margin:0 0 6px 0;"><small>Preview:</small></p>
                <img id="imagePreview" src="#" alt="Preview" style="max-width:100%;height:auto;border-radius:6px;" />
            </div>
        </div>

        <input type="hidden" id="lat" name="lat" value="<?= htmlspecialchars($lat ?? ($_POST['lat'] ?? '')) ?>">
        <input type="hidden" id="lng" name="lng" value="<?= htmlspecialchars($lng ?? ($_POST['lng'] ?? '')) ?>">

        <div class="alert alert-info" id="locationAlert" style="display:<?= (empty($lat) && empty($_POST['lat'] ?? '')) ? 'block' : 'none' ?>;">
            <strong>Location required:</strong> Please select a location on the map before submitting.
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Create</button>
            <a class="btn btn-secondary" href="<?= $link->url('home.index') ?>">Cancel</a>
        </div>
    </form>
</div>

<script src="/js/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof setupNewPostForm === 'function') setupNewPostForm();

    // Form validation - only on submit
    const form = document.getElementById('newPostForm');
    const titleInput = document.getElementById('name');
    const descInput = document.getElementById('description');
    const imageInput = document.getElementById('image');
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const locationAlert = document.getElementById('locationAlert');

    // Image preview - no validation blocking
    imageInput.addEventListener('change', function(ev){
        const f = ev.target.files && ev.target.files[0];
        if (!f){
            document.getElementById('imagePreviewContainer').style.display='none';
            return;
        }
        if (!f.type.startsWith('image/')){
            document.getElementById('imagePreviewContainer').style.display='none';
            return;
        }
        const reader = new FileReader();
        reader.onload=function(e){
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreviewContainer').style.display='block';
        };
        reader.readAsDataURL(f);
    });

    form.addEventListener('submit', function(ev) {
        const lat = latInput.value;
        const lng = lngInput.value;
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

        if (!lat || !lng) {
            locationAlert.style.display = 'block';
            hasErrors = true;
        } else {
            locationAlert.style.display = 'none';
        }

        if (hasErrors) {
            ev.preventDefault();
            ev.stopPropagation();
        }
    });
});
</script>



