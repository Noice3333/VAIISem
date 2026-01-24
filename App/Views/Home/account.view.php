<?php
/** @var array $data */
/** @var \Framework\Support\LinkGenerator $link */

// Ensure $message is defined to avoid undefined variable notices in the view
$message = $data['message'] ?? null;
?>

<form class="form-signin" method="post">
    <div class="m-3" role="alert">
        <?= $message ?>
    </div>
    <div class="form-label-group mb-3">
        <label for="name" class="form-label">Name (the one other users see)</label>
        <input name="name" type="text" id="name" class="form-control"
               placeholder="<?= $_SESSION['user']->getName() ?>">
    </div>
    <div class="form-label-group mb-3">
        <label for="login" class="form-label">Username (the one you use to log in)</label>
        <input name="login" type="text" id="login" class="form-control"
               placeholder="<?= $_SESSION['user']->getUsername() ?>">
    </div>
    <div class="form-label-group mb-3">
        <label for="password" class="form-label">Password</label>
        <input name="password" type="password" id="password" class="form-control"
               placeholder="Password">
    </div>
    <div class="row formRow">
        <div class="col-md-12 col-lg-6 d-flex justify-content-center align-items-center">
            <button class="btn btn-primary customButton mb-3" type="submit" name="submit">
                Edit
            </button>
        </div>
        <div class="col-md-12 col-lg-6 d-flex justify-content-center align-items-center">
            <button type="submit" class="btn btn-danger customButton mb-3" name="delete"
                    onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                Delete
            </button>
        </div>
    </div>
</form>
