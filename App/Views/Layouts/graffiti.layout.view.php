<?php

/** @var string $contentHTML */
/** @var \Framework\Core\IAuthenticator $auth */
/** @var \Framework\Support\LinkGenerator $link */
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <title><?= App\Configuration::APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
            crossorigin="anonymous"></script>
    <!-- Leaflet and MarkerCluster (available for pages that use maps) -->
    <link rel="stylesheet" href="<?= $link->asset('vendor/leaflet/leaflet.css') ?>">
    <link rel="stylesheet" href="<?= $link->asset('vendor/leaflet/MarkerCluster.css') ?>">
    <link rel="stylesheet" href="<?= $link->asset('vendor/leaflet/MarkerCluster.Default.css') ?>">
    <script src="<?= $link->asset('vendor/leaflet/leaflet.js') ?>"></script>
    <script src="<?= $link->asset('vendor/leaflet/leaflet.markercluster.js') ?>"></script>
    <link rel="stylesheet" href="<?= $link->asset('css/styl.css') ?>">
    <script src="<?= $link->asset('js/script.js') ?>"></script>
</head>
<body>
<div class="navbar navbarG navbar-expand-lg" >
    <div class="container-fluid">
        <a class="nav-link" href="<?= $link->url('home.index') ?>">
            <img class="smallIcon" src="<?= $link->asset('images/graffiti.png') ?>"
                 title="<?= App\Configuration::APP_NAME ?>" alt="Return">
        </a>
        <?php if ($auth?->isLogged()) { ?>
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link navbarText" href="<?= $link->url('home.post') ?>">Posts</a>
                </li>
            </ul>
            <span class="navbarText">Logged in user: <b><?= $auth?->user?->getName() ?></b></span>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link navbarText" href="<?= $link->url('home.account') ?>">Account</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link navbarText" href="<?= $link->url('auth.logout') ?>">Log out</a>
                </li>
            </ul>
        <?php } else { ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link navbarText" href="<?= App\Configuration::LOGIN_URL ?>">Log in</a>
                </li>
            </ul>
        <?php } ?>
    </div>
</div>
<div class="container-fluid">
    <div class="web-content">
        <?= $contentHTML ?>
    </div>
</div>
</body>
</html>
