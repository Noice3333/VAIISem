<?php
/** @var array $data */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Core\IAuthenticator $auth */

// Ensure $message is defined to avoid undefined variable notices in the view
$message = $data['message'] ?? null;
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">My Account</h2>

            <?php if ($auth?->isLogged()) { ?>

                <!-- Edit Account Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div id="editMessage" class="mb-3" style="display:none;"></div>

                        <form id="editAccountForm" novalidate data-username="<?= htmlspecialchars($_SESSION['user']->getUsername()) ?>">
                            <div class="form-label-group mb-3">
                                <label for="name" class="form-label">Name (the one other users see)</label>
                                <input name="name" type="text" id="name" class="form-control"
                                       placeholder="<?= htmlspecialchars($_SESSION['user']->getName()) ?>"
                                       maxlength="255">
                                <div class="invalid-feedback">Name must be 1-255 characters</div>
                            </div>

                            <div class="form-label-group mb-3">
                                <label for="login" class="form-label">Username (the one you use to log in)</label>
                                <input name="login" type="text" id="login" class="form-control"
                                       placeholder="<?= htmlspecialchars($_SESSION['user']->getUsername()) ?>"
                                       maxlength="50" minlength="3">
                                <div class="invalid-feedback">Username must be 3-50 characters</div>
                            </div>

                            <div class="form-label-group mb-3">
                                <label for="password" class="form-label">Password (leave blank to keep current password)</label>
                                <input name="password" type="password" id="password" class="form-control"
                                       placeholder="New password (optional)" minlength="8">
                                <div class="invalid-feedback">Password must be at least 8 characters if provided</div>
                                <small class="form-text text-muted">If you want to change your password, enter a new one with at least 8 characters.</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit" name="submit">
                                    Save Changes
                                </button>
                                <a href="<?= $link->url('home.index') ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete Account Section -->
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-danger mb-3">
                            <strong>⚠️ Warning:</strong> Deleting your account is permanent and cannot be undone.
                            All your posts, comments, and likes will be deleted.
                        </p>
                        <button class="btn btn-danger" id="deleteAccountBtn">Delete Account</button>
                    </div>
                </div>

            <?php } else { ?>
                <div class="alert alert-warning">
                    You need to be logged in to view this page.
                </div>
            <?php } ?>
        </div>
    </div>
</div>


<script src="/js/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof setupAccountForm === 'function') setupAccountForm();
});
</script>
