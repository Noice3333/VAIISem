<?php

/** @var \Framework\Support\LinkGenerator $link */
?>

<div class="row homeScreenContainer">
    <!-- Left sidebar: start hidden (d-none). It will be shown when a post is clicked -->
    <div id="sidebar" class="col-4 borderSep d-none">
        <div id="sidebarContent" class="p-3" style="max-height:calc(100vh - 20px);overflow:auto;">
            <!-- Content inserted dynamically when a marker is clicked -->
        </div>
    </div>

    <!-- Map column: starts full width, will shrink to col-8 when sidebar is shown -->
    <div id="mapCol" class="col-12 p-1">
        <div class="text-center">
            <!-- Replaced static iframe with interactive Leaflet map -->
            <!-- Leaflet/MarkerCluster are included in the layout head -->
            <!-- Map styles moved to public/css/styl.css but we add a minimum here for safety -->
            <div id="map" aria-label="Map showing posts" style="width:100%;height:70vh;min-height:420px;"></div>

            <!-- External scripts and initializer -->
            <script src="/js/script.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function(){
                    if (typeof initHomeMap !== 'function' || typeof buildHomeMapConfig !== 'function') return;
                    const cfg = buildHomeMapConfig({
                        apiUrl: '<?= $link->url("home.post", ["json"=>1]) ?>',
                        commentsApi: '<?= $link->url("post.comments", ["json"=>1]) ?>',
                        commentCreateApi: '<?= $link->url("post.commentCreate", ["json"=>1]) ?>',
                        commentDeleteApi: '<?= $link->url("post.commentDelete", ["json"=>1]) ?>',
                        likeApi: '<?= $link->url("post.toggleLike", ["json"=>1]) ?>',
                        newPostUrl: '<?= $link->url("post.new") ?>',
                        loginUrl: '<?= \App\Configuration::LOGIN_URL ?>',
                        isLogged: <?= (isset($auth) && $auth?->isLogged()) ? 'true' : 'false' ?>,
                        currentUserId: <?= (isset($_SESSION['user']) && method_exists($_SESSION['user'], 'getId')) ? json_encode($_SESSION['user']->getId()) : 'null' ?>
                    });
                    initHomeMap(cfg);
                });
            </script>

        </div>
    </div>
</div>
