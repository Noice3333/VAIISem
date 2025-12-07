<?php

/** @var array $data */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container" style="background-color: aqua; max-width: 60%; margin: auto;">
    <div class="row justify-content-center">
        <div class="text-center text-danger mb-3">
            <?= @$data['message'] ?>
        </div>
        <form class="form-signin" method="post" action="<?= $link->url("register") ?>">
            <div class="form-label-group mb-3">
                <label for="login" class="form-label">Login</label>
                <input name="login" type="text" id="login" class="form-control" placeholder="Login"
                       required autofocus>
            </div>
            <div class="form-label-group mb-3">
                <label for="password" class="form-label">Password</label>
                <input name="password" type="password" id="password" class="form-control"
                       placeholder="Password" required>
            </div>
            <div class="form-label-group mb-3">
                <label for="repeat_password" class="form-label">Login</label>
                <input name="repeat_password" type="text" id="repeat_password" class="form-control" placeholder="Same password"
                       required>
            </div>
            <div class="row formRow">
                <div class="col" >
                    <button class="btn btn-danger customButton" type="submit" name="submit">
                        Register
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>