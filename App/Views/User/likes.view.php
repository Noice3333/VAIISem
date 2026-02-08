<?php
/** @var array[] $likedPosts */
/** @var array[] $likedComments */
/** @var int $userId */
/** @var \Framework\Support\LinkGenerator $link */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col mt-5">
            <h2 class="content-page-title">My Likes</h2>

            <?php if (empty($likedPosts) && empty($likedComments)): ?>
                <div class="alert alert-info">
                    You haven't liked anything yet.
                </div>
            <?php else: ?>

                <!-- Liked Posts Section -->
                <?php if (!empty($likedPosts)): ?>
                    <h4 class="content-section-title">Posts</h4>
                    <div>
                        <?php foreach ($likedPosts as $postData): ?>
                            <div class="content-card post-card">
                                <div style="flex: 1; display: flex;">
                                    <?php
                                    $img = isset($postData['image']) ? $postData['image'] : null;
                                    $imgUrl = null;
                                    if (!empty($img)) {
                                        $fname = basename($img);
                                        $imgUrl = htmlspecialchars($img) . (strpos($img, '?') !== false ? '&' : '?') . '_v=' . urlencode($fname);
                                    }
                                    ?>

                                    <?php if ($imgUrl): ?>
                                        <div class="content-card-image">
                                            <img src="<?= $imgUrl ?>" alt="Post image" />
                                        </div>
                                    <?php endif; ?>

                                    <div class="content-card-content">
                                        <div class="content-card-title">
                                            <strong>Title:</strong>
                                            <a href="<?= htmlspecialchars($link->url('home.index', ['open_post_id' => $postData['id']])) ?>">
                                                <?= htmlspecialchars($postData['title'] ?? 'Untitled') ?>
                                            </a>
                                        </div>
                                        <div class="content-card-author"><strong>By:</strong> <?= htmlspecialchars($postData['username'] ?? 'Unknown') ?></div>
                                        <div class="content-card-date"><strong>Posted:</strong> <?= htmlspecialchars($postData['created_at'] ?? '') ?></div>
                                        <div class="content-card-body"><strong>Description:</strong> <?= nl2br(htmlspecialchars($postData['description'] ?? '')) ?></div>
                                        <div class="content-card-footer">
                                            <span class="location-info">
                                                <strong>Location:</strong> <?= htmlspecialchars($postData['latitude'] ?? '') ?>, <?= htmlspecialchars($postData['longitude'] ?? '') ?>
                                            </span>
                                            <span class="like-count"><?= $postData['like_count'] ?? 0 ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="content-card-actions">
                                    <a href="<?= htmlspecialchars($link->url('home.index', ['open_post_id' => $postData['id']])) ?>" class="btn btn-primary btn-sm">Open on map</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Liked Comments Section -->
                <?php if (!empty($likedComments)): ?>
                    <h4 class="content-section-title">Comments</h4>
                    <div>
                        <?php foreach ($likedComments as $commentData): ?>
                            <div class="content-card comment-card">
                                <div class="content-card-content">
                                    <div class="content-card-title">
                                        <strong>Post:</strong>
                                        <?php if (isset($commentData['post']) && $commentData['post'] !== null && method_exists($commentData['post'], 'getId')): ?>
                                            <a href="<?= htmlspecialchars($link->url('home.index', ['open_post_id' => $commentData['post']->getId()])) ?>">
                                                <?= htmlspecialchars($commentData['post']->getTitle() ?? 'Untitled') ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">(Post deleted)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="content-card-author"><strong>By:</strong> <?= htmlspecialchars($commentData['username'] ?? 'Unknown') ?></div>
                                    <div class="content-card-date"><strong>Posted:</strong> <?= htmlspecialchars($commentData['created_at'] ?? '') ?></div>
                                    <div class="content-card-body"><strong>Comment:</strong> <?= nl2br(htmlspecialchars($commentData['content'] ?? '')) ?></div>
                                    <div class="content-card-footer">
                                        <span class="like-count"><?= $commentData['like_count'] ?? 0 ?></span>
                                    </div>
                                </div>
                                <div class="content-card-actions">
                                    <?php if (isset($commentData['post']) && $commentData['post'] !== null && method_exists($commentData['post'], 'getId')): ?>
                                        <a href="<?= htmlspecialchars($link->url('home.index', ['open_post_id' => $commentData['post']->getId()])) ?>" class="btn btn-primary btn-sm">View post</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
