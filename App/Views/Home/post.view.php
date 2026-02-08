<?php

// This view is kept as a simple HTML fallback. API logic was moved to App\Controllers\HomeController::post
?>

<div class="container-fluid">
    <div class="row">
        <div class="col mt-5">
            <h2 class="content-page-title">Your Posts</h2>

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
                    <div>
                        <?php foreach ($posts as $p): ?>
                            <div class="content-card post-card">
                                <div style="flex: 1; display: flex;">
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
                                        <div class="content-card-image">
                                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Post image" />
                                        </div>
                                    <?php endif; ?>

                                    <div class="content-card-content">
                                        <div class="content-card-title"><strong>Title:</strong> <?= htmlspecialchars($p->getTitle() ?? 'Post') ?></div>
                                        <div class="content-card-author"><strong>By:</strong> <?= htmlspecialchars($_SESSION['user']->getName() ?? $_SESSION['user']->getUsername() ?? 'Unknown') ?></div>
                                        <div class="content-card-date"><strong>Posted:</strong> <?= htmlspecialchars($p->getCreatedAt() ?? '') ?></div>
                                        <div class="content-card-body"><strong>Description:</strong> <?= nl2br(htmlspecialchars($p->getDescription() ?? '')) ?></div>
                                        <div class="location-info">
                                            <strong>Location:</strong> <?= htmlspecialchars($p->getLatitude() ?? '') ?>, <?= htmlspecialchars($p->getLongitude() ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="content-card-actions">
                                    <?php
                                    // Generate URL back to home with open_post_id parameter
                                    $homeUrl = isset($link) ? $link->url('home.index', ['open_post_id' => $p->getId()]) : ('?c=home&a=index&open_post_id=' . urlencode($p->getId()));
                                    $editUrl = isset($link) ? $link->url('post.edit', ['id' => $p->getId()]) : ('?c=post&a=edit&id=' . urlencode($p->getId()));
                                    ?>
                                    <a href="<?= htmlspecialchars($homeUrl) ?>" class="btn btn-primary btn-sm">Open on map</a>
                                    <a href="<?= htmlspecialchars($editUrl) ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
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
