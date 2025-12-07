<?php

/** @var Array $dataVars */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container loginBox">
    <div class="row justify-content-center">
        <div class="text-center text-danger mb-3">
            <?= @$dataVars['message'] ?>
        </div>
        <form class="form-signin needs-validation" method="post" id="gForm"
              action="<?= $link->url("register") ?>"
            novalidate>
            <div class="form-label-group mb-3">
                <label for="name" class="form-label">Name (this will be visible to other users)</label>
                <input name="name" type="text" id="name" class="form-control" placeholder="Name"
                       required autofocus>
                <div class="invalid-feedback">
                    Please provide a name.
                </div>
            </div>
            <div class="form-label-group mb-3">
                <label for="login" class="form-label">Username (you will use this to log in)</label>
                <input name="login" type="text" id="login" class="form-control" placeholder="Login"
                       required >
                <div class="invalid-feedback">
                    Please provide a username.
                </div>
            </div>
            <div class="form-label-group mb-3">
                <label for="password" class="form-label">Password</label>
                <input name="password" type="password" id="password" class="form-control"
                       placeholder="Password">
                <div class="invalid-feedback">
                    Password needs to be at least 8 characters long.
                </div>
            </div>
            <div class="form-label-group mb-3">
                <label for="repeat_password" class="form-label">Repeat password</label>
                <input name="repeat_password" type="password" id="repeat_password" class="form-control" placeholder="Same password"
                       >
                <div class="invalid-feedback">
                    Passwords need to match.
                </div>
            </div>
            <div class="row formRow">
                <div class="col d-flex justify-content-center align-items-center" >
                    <button class="btn btn-danger customButton mb-3" type="submit" name="submit">
                        Register
                    </button>
                </div>
            </div>
            <script src="<?= $link->asset('js/validation.js') ?>"></script>
        </form>
    </div>
</div>
