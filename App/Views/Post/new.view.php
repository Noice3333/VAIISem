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

    <form method="post" action="<?= $link->url('post.create') ?>" enctype="multipart/form-data" id="newPostForm">
        <div class="mb-3">
            <label for="name" class="form-label">Title</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Image (optional)</label>
            <input class="form-control" type="file" id="image" name="image" accept="image/*">
            <div id="imagePreviewContainer" style="margin-top:8px;display:none;">
                <p style="margin:0 0 6px 0;"><small>Preview:</small></p>
                <img id="imagePreview" src="#" alt="Preview" style="max-width:100%;height:auto;border-radius:6px;" />
            </div>
        </div>

        <input type="hidden" id="lat" name="lat" value="<?= htmlspecialchars($lat ?? ($_POST['lat'] ?? '')) ?>">
        <input type="hidden" id="lng" name="lng" value="<?= htmlspecialchars($lng ?? ($_POST['lng'] ?? '')) ?>">

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
});
</script>
