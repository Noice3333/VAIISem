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
}