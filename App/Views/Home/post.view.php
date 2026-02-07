<?php

// This view is kept as a simple HTML fallback. API logic was moved to App\Controllers\HomeController::post
?>

<div class="container-fluid">
    <div class="row">
        <div class="col mt-5">
            <h3>Your posts</h3>

            <?php
            // If no user is logged in show the required message
            if (!isset($_SESSION['user']) || !method_exists($_SESSION['user'], 'getId')):
            ?>
                <div class="alert alert-warning" role="alert">
                    You are not supposed to be here!
                </div>
            <?php
            else:
                // Fetch posts for current user
                try {
                    $userId = $_SESSION['user']->getId();
                    $posts = \App\Models\Post::getAll('`user_id` = ?', [$userId], 'created_at DESC');
                } catch (Throwable $t) {
                    $posts = [];
                }

                if (empty($posts)):
                ?>
                    <div class="alert alert-info">You have no posts yet.</div>
                <?php
                else:
                ?>
                    <div class="list-group">
                        <?php foreach ($posts as $p): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-start">
                                    <?php
                                    // Prepare image URL with simple cache-busting by filename
                                    $img = $p->getImage();
                                    $imgUrl = null;
                                    if (!empty($img)) {
                                        $fname = basename($img);
                                        $imgUrl = $img . (strpos($img, '?') !== false ? '&' : '?') . '_v=' . urlencode($fname);
                                    }
                                    ?>

                                    <?php if ($imgUrl): ?>
                                        <div class="me-3" style="flex:0 0 120px;">
                                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Post image" style="width:120px;height:auto;border-radius:6px;object-fit:cover;" />
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($p->getTitle() ?? 'Post') ?></h6>
                                        <p class="mb-1 small text-muted"><?= htmlspecialchars($p->getCreatedAt() ?? '') ?></p>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($p->getDescription() ?? '')) ?></p>
                                    </div>
                                </div>
                                <div class="ms-3 d-flex flex-column align-items-end gap-2">
                                    <?php
                                    // Generate URL back to home with open_post_id parameter
                                    $homeUrl = isset($link) ? $link->url('home.index', ['open_post_id' => $p->getId()]) : ('?c=home&a=index&open_post_id=' . urlencode($p->getId()));
                                    $editUrl = isset($link) ? $link->url('post.edit', ['id' => $p->getId()]) : ('?c=post&a=edit&id=' . urlencode($p->getId()));
                                    ?>
                                    <a href="<?= htmlspecialchars($homeUrl) ?>" class="btn btn-primary">Open on map</a>
                                    <a href="<?= htmlspecialchars($editUrl) ?>" class="btn btn-outline-secondary">Edit</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php
                endif;
            endif;
            ?>

        </div>
    </div>
</div>
