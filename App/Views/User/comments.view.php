<?php
/** @var array[] $comments */
/** @var \Framework\Support\LinkGenerator $link */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col mt-5">
            <h2 class="content-page-title">My Comments</h2>

            <?php if (empty($comments)): ?>
                <div class="alert alert-info">
                    You haven't posted any comments yet.
                </div>
            <?php else: ?>
                <div>
                    <?php foreach ($comments as $commentData): ?>
                        <div class="content-card comment-card">
                            <div class="content-card-content">
                                <div class="content-card-title">
                                    <strong>Post:</strong>
                                    <?php if (isset($commentData['post']) && $commentData['post'] !== null): ?>
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
                                <button class="btn btn-sm btn-danger delete-comment-btn" data-id="<?= $commentData['id'] ?>">
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    if (typeof initPage === 'function') initPage();
});
</script>
