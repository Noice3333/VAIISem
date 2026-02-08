<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

class PostController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->html();
    }

    // Show new post form
    public function new(Request $request): Response
    {
        $auth = $this->app->getAuth();
        if (!$auth || !$auth->isLogged()) {
            return $this->redirect(\App\Configuration::LOGIN_URL);
        }

        $lat = $request->get('lat');
        $lng = $request->get('lng');
        return $this->html(['lat' => $lat, 'lng' => $lng]);
    }

    // Handle form submission
    public function create(Request $request): Response
    {
        $auth = $this->app->getAuth();
        if (!$auth || !$auth->isLogged()) {
            return $this->redirect(\App\Configuration::LOGIN_URL);
        }

        if (!$request->isPost()) {
            return $this->redirect($this->url('post.new'));
        }

        $title = trim((string)($request->post('name') ?? ''));
        $description = trim((string)($request->post('description') ?? ''));
        $lat = $request->post('lat');
        $lng = $request->post('lng');

        if ($title === '' || $description === '' || $lat === null || $lng === null) {
            return $this->redirect($this->url('post.new'));
        }

        $lat = (float)$lat;
        $lng = (float)$lng;
        if (abs($lat) > 90 || abs($lng) > 180) {
            return $this->redirect($this->url('post.new'));
        }

        // Handle uploaded image — ensure uploads dir exists at project root (two levels up)
        $imagePath = null;
        try {
            $uploaded = $request->file('image');
            if ($uploaded !== null && $uploaded->isOk()) {
                // project root = two levels up from App/Controllers (e.g. /var/www/html)
                $root = dirname(__DIR__, 2);
                $uploadsDir = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';

                if (!is_dir($uploadsDir)) {
                    if (!@mkdir($uploadsDir, 0755, true)) {
                        error_log('PostController: failed to create uploads dir: ' . $uploadsDir);
                    }
                }

                if (!is_writable($uploadsDir)) {
                    error_log('PostController: uploads dir not writable: ' . $uploadsDir);
                }

                $origName = $uploaded->getName();
                $ext = pathinfo($origName, PATHINFO_EXTENSION);
                $ext = preg_replace('/[^a-zA-Z0-9]/', '', strtolower((string)$ext));
                $safe = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
                $dest = $uploadsDir . DIRECTORY_SEPARATOR . $safe;

                if ($uploaded->store($dest)) {
                    $imagePath = '/uploads/' . $safe;
                } else {
                    error_log('PostController: failed to move uploaded file to ' . $dest);
                }
            }
        } catch (\Throwable $e) {
            error_log('PostController upload error: ' . $e->getMessage());
            $imagePath = null;
        }

        // Create post
        $user = $auth->user;
        $userId = ($user && method_exists($user, 'getId')) ? $user->getId() : null;

        try {
            \App\Models\Post::create([
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'image' => $imagePath,
                'latitude' => $lat,
                'longitude' => $lng
            ]);
        } catch (\Exception $ex) {
            return $this->redirect($this->url('post.new'));
        }

        return $this->redirect($this->url('home.index'));
    }

    // Show edit form for a post
    public function edit(Request $request): Response
    {
        $auth = $this->app->getAuth();
        if (!$auth || !$auth->isLogged()) {
            return $this->redirect(\App\Configuration::LOGIN_URL);
        }

        $id = $request->get('id');
        if ($id === null) {
            return $this->redirect($this->url('home.index'));
        }

        $post = \App\Models\Post::getOne((int)$id);
        if ($post === null) {
            return $this->redirect($this->url('home.index'));
        }

        // Ensure ownership
        $user = $auth->user;
        $userId = ($user && method_exists($user, 'getId')) ? $user->getId() : null;
        if ($post->getUserId() !== $userId) {
            return $this->redirect($this->url('home.index'));
        }

        return $this->html(['post' => $post]);
    }

    // Handle post update
    public function update(Request $request): Response
    {
        $auth = $this->app->getAuth();
        if (!$auth || !$auth->isLogged()) {
            return $this->redirect(\App\Configuration::LOGIN_URL);
        }

        if (!$request->isPost()) {
            return $this->redirect($this->url('home.index'));
        }

        $id = $request->post('id');
        if ($id === null) {
            return $this->redirect($this->url('home.index'));
        }

        $post = \App\Models\Post::getOne((int)$id);
        if ($post === null) {
            return $this->redirect($this->url('home.index'));
        }

        // Ownership check
        $user = $auth->user;
        $userId = ($user && method_exists($user, 'getId')) ? $user->getId() : null;
        if ($post->getUserId() !== $userId) {
            return $this->redirect($this->url('home.index'));
        }

        $title = trim((string)($request->post('name') ?? ''));
        $description = trim((string)($request->post('description') ?? ''));
        $lat = $request->post('lat');
        $lng = $request->post('lng');

        if ($title === '' || $description === '' || $lat === null || $lng === null) {
            return $this->redirect($this->url('post.edit', ['id' => $id]));
        }

        $lat = (float)$lat;
        $lng = (float)$lng;
        if (abs($lat) > 90 || abs($lng) > 180) {
            return $this->redirect($this->url('post.edit', ['id' => $id]));
        }

        // Handle new image upload (optional)
        $imagePath = $post->getImage();
        try {
            $uploaded = $request->file('image');
            if ($uploaded !== null && $uploaded->isOk()) {
                $root = dirname(__DIR__, 2);
                $uploadsDir = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
                if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0755, true);

                $origName = $uploaded->getName();
                $ext = pathinfo($origName, PATHINFO_EXTENSION);
                $ext = preg_replace('/[^a-zA-Z0-9]/', '', strtolower((string)$ext));
                $safe = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
                $dest = $uploadsDir . DIRECTORY_SEPARATOR . $safe;

                if ($uploaded->store($dest)) {
                    $imagePath = '/uploads/' . $safe;
                }
            }
        } catch (\Throwable $e) {
            // ignore upload errors for update
        }

        try {
            \App\Models\Post::updateById((int)$id, [
                'title' => $title,
                'description' => $description,
                'image' => $imagePath,
                'latitude' => $lat,
                'longitude' => $lng
            ]);
        } catch (\Exception $e) {
            return $this->redirect($this->url('post.edit', ['id' => $id]));
        }

        return $this->redirect($this->url('home.index', ['open_post_id' => $id]));
    }

    // Handle post deletion
    public function delete(Request $request): Response
    {
        $auth = $this->app->getAuth();
        if (!$auth || !$auth->isLogged()) {
            return $this->redirect(\App\Configuration::LOGIN_URL);
        }

        if (!$request->isPost()) {
            return $this->redirect($this->url('home.index'));
        }

        $id = $request->post('id');
        if ($id === null) {
            return $this->redirect($this->url('home.index'));
        }

        $post = \App\Models\Post::getOne((int)$id);
        if ($post === null) {
            return $this->redirect($this->url('home.index'));
        }

        // Ownership check
        $user = $auth->user;
        $userId = ($user && method_exists($user, 'getId')) ? $user->getId() : null;
        if ($post->getUserId() !== $userId) {
            return $this->redirect($this->url('home.index'));
        }

        try {
            \App\Models\Post::deleteById((int)$id);
        } catch (\Exception $e) {
            // ignore
        }

        return $this->redirect($this->url('home.index'));
    }

    // API: return comments for a post and post-like summary
    public function comments(Request $request): Response
    {
        $isJson = ($request->get('json') === '1') || $request->wantsJson() || $request->isJson();
        if (!$isJson) {
            return $this->json(['error' => 'JSON required'])->setStatusCode(400);
        }

        $postId = $request->get('post_id');
        if ($postId === null) {
            return $this->json(['error' => 'Missing post_id'])->setStatusCode(400);
        }

        $post = \App\Models\Post::getOne((int)$postId);
        if ($post === null) {
            return $this->json(['error' => 'Post not found'])->setStatusCode(404);
        }

        $comments = \App\Models\Comment::getForPost((int)$postId);
        $result = [];
        $currentUserId = isset($_SESSION['user']) && method_exists($_SESSION['user'], 'getId') ? $_SESSION['user']->getId() : null;
        foreach ($comments as $c) {
            $user = null;
            if ($c->getUserId() !== null) {
                try { $user = \App\Models\User::getOne($c->getUserId()); } catch (\Throwable $t) { $user = null; }
            }
            $username = $user ? ($user->getUsername() ?? ($user->login ?? null)) : null;

            $likeCount = \App\Models\Like::countForTarget('comment', $c->getId());
            $liked = $currentUserId ? \App\Models\Like::userHasLiked($currentUserId, 'comment', $c->getId()) : false;

            $result[] = [
                'id' => $c->getId(),
                'post_id' => $c->getPostId(),
                'user_id' => $c->getUserId(),
                'username' => $username,
                'content' => $c->getContent(),
                'created_at' => $c->getCreatedAt(),
                'like_count' => $likeCount,
                'liked' => $liked
            ];
        }

        $postLikeCount = \App\Models\Like::countForTarget('post', $post->getId());
        $postLiked = $currentUserId ? \App\Models\Like::userHasLiked($currentUserId, 'post', $post->getId()) : false;

        return $this->json(['post' => [
            'id' => $post->getId(),
            'like_count' => $postLikeCount,
            'liked' => $postLiked
        ], 'comments' => $result]);
    }

    // API: create a comment (JSON POST)
    public function commentCreate(Request $request): Response
    {
        $isJson = ($request->get('json') === '1') || $request->wantsJson() || $request->isJson();
        if (!$isJson) {
            return $this->json(['error' => 'JSON required'])->setStatusCode(400);
        }

        $auth = $this->app->getAuth();
        if (!$auth || !$auth->isLogged()) {
            return $this->json(['error' => 'Authentication required'])->setStatusCode(401);
        }

        $input = $request->isJson() ? $request->json() : null;
        if (!$input) {
            // try standard POST
            $postId = $request->post('post_id');
            $content = trim((string)($request->post('content') ?? ''));
        } else {
            $input = is_object($input) ? json_decode(json_encode($input), true) : $input;
            $postId = $input['post_id'] ?? null;
            $content = trim((string)($input['content'] ?? ''));
        }

        if ($postId === null || $content === '') {
            return $this->json(['error' => 'Missing fields'])->setStatusCode(400);
        }

        $post = \App\Models\Post::getOne((int)$postId);
        if ($post === null) {
            return $this->json(['error' => 'Post not found'])->setStatusCode(404);
        }

        $userId = $_SESSION['user']->getId();
        try {
            $c = \App\Models\Comment::create([
                'post_id' => (int)$postId,
                'user_id' => $userId,
                'content' => $content
            ]);
            return $this->json(['ok' => true, 'comment' => [
                'id' => $c->getId(),
                'post_id' => $c->getPostId(),
                'user_id' => $c->getUserId(),
                'content' => $c->getContent(),
                'created_at' => $c->getCreatedAt(),
                'like_count' => 0,
                'liked' => false
            ]]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Insert failed'])->setStatusCode(500);
        }
    }

    // API: delete a comment
    public function commentDelete(Request $request): Response
    {
        $auth = $this->app->getAuth();
        if (!$auth || !$auth->isLogged()) {
            return $this->json(['error' => 'Authentication required'])->setStatusCode(401);
        }

        $id = $request->post('id') ?? $request->get('id');
        if ($id === null) {
            return $this->json(['error' => 'Missing id'])->setStatusCode(400);
        }

        $c = \App\Models\Comment::getOne((int)$id);
        if ($c === null) {
            return $this->json(['error' => 'Comment not found'])->setStatusCode(404);
        }

        $userId = $_SESSION['user']->getId();
        if ($c->getUserId() !== $userId) {
            return $this->json(['error' => 'Forbidden'])->setStatusCode(403);
        }

        try {
            // delete likes on the comment as well
            \App\Models\Like::deleteByTarget('comment', (int)$id);
            \App\Models\Comment::deleteById((int)$id);
            return $this->json(['ok' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Delete failed'])->setStatusCode(500);
        }
    }

    // API: toggle like on a post or comment
    public function toggleLike(Request $request): Response
    {
        $auth = $this->app->getAuth();
        if (!$auth || !$auth->isLogged()) {
            return $this->json(['error' => 'Authentication required'])->setStatusCode(401);
        }

        $input = $request->isJson() ? $request->json() : null;
        if (!$input) {
            $targetType = $request->post('target_type') ?? $request->get('target_type');
            $targetId = $request->post('target_id') ?? $request->get('target_id');
        } else {
            $input = is_object($input) ? json_decode(json_encode($input), true) : $input;
            $targetType = $input['target_type'] ?? null;
            $targetId = $input['target_id'] ?? null;
        }

        if (!$targetType || !$targetId) {
            return $this->json(['error' => 'Missing fields'])->setStatusCode(400);
        }

        $targetType = strtolower((string)$targetType);
        if (!in_array($targetType, ['post', 'comment'])) {
            return $this->json(['error' => 'Invalid target_type'])->setStatusCode(400);
        }

        $userId = $_SESSION['user']->getId();
        try {
            $liked = \App\Models\Like::toggleLike((int)$userId, $targetType, (int)$targetId);
            $count = \App\Models\Like::countForTarget($targetType, (int)$targetId);
            return $this->json(['ok' => true, 'liked' => $liked, 'count' => $count]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Operation failed'])->setStatusCode(500);
        }
    }
}
