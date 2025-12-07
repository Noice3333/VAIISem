<?php
/** @var array $dataVars */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container loginBox">
    <div class="row justify-content-center">
        <div class="text-center text-danger mb-3">
            <?= $dataVars['message'] ?>
        </div>
        <form class="form-signin" method="post">
            <div class="form-label-group mb-3">
                <label for="login" class="form-label">Username (the one you filled into the second field when registering)</label>
                <input name="login" type="text" id="login" class="form-control" placeholder="Username"
                       required autofocus>
            </div>
            <div class="form-label-group mb-3">
                <label for="password" class="form-label">Password</label>
                <input name="password" type="password" id="password" class="form-control"
                       placeholder="Password" required>
            </div>
            <div class="row formRow">
                <div class="col-md-12 col-lg-6 d-flex justify-content-center align-items-center">
                    <button class="btn btn-primary customButton mb-3" type="submit" name="submit">
                        Log in
                    </button>
                </div>
                <div class="col-md-12 col-lg-6 d-flex justify-content-center align-items-center">
                    <button type="button" class="btn btn-danger customButton mb-3">
                        <a class="nav-link" href="<?= $link->url('auth.register') ?>">Sign up</a>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

