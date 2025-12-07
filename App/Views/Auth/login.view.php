<?php
/** @var array $dataVars */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container" style="background-color: aqua; max-width: 60%; margin: auto;">
    <div class="row justify-content-center">
        <div class="text-center text-danger mb-3">
            <?= $dataVars['message'] ?>
        </div>
        <form class="form-signin" method="post">
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
            <div class="row formRow">
                <div class="col" >
                    <button class="btn btn-primary customButton" type="submit" name="submit">
                        Log in
                    </button>
                </div>
                <div class="col">
                    <button type="button" class="btn btn-danger customButton">
                        <a class="nav-link" href="<?= $link->url('auth.register') ?>">Register</a>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

