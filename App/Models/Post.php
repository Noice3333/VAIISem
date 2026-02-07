<?php

namespace App\Models;

use Framework\Core\Model;
use Framework\Http\Request;
use Exception;

class Post extends Model
{
    // Map to 'posts' table
    protected static ?string $tableName = 'posts';

    // Model properties matching DB columns
    protected ?int $id = null;
    protected ?int $user_id = null;
    protected ?string $title = null;
    protected ?string $description = null;
    protected ?string $image = null;
    protected ?float $latitude = null;
    protected ?float $longitude = null;
    protected ?string $created_at = null;

    // Convenience factory to create and save a post
    public static function create(array $data): self
    {
        $post = new self();
        if (isset($data['user_id'])) $post->user_id = $data['user_id'];
        if (isset($data['title'])) $post->title = $data['title'];
        if (isset($data['description'])) $post->description = $data['description'];
        if (isset($data['image'])) $post->image = $data['image'];
        if (isset($data['latitude'])) $post->latitude = (float)$data['latitude'];
        if (isset($data['longitude'])) $post->longitude = (float)$data['longitude'];
        // ensure created_at is set to now so DB default isn't overwritten with NULL
        $post->created_at = date('Y-m-d H:i:s');

        $post->save();
        return $post;
    }

    // Optional: allow filling from Request
    public function setFromRequest(Request $request): void
    {
        $data = $request->isPost() ? $request->post() : $request->get();
        if (isset($data['title'])) $this->title = $data['title'];
        if (isset($data['description'])) $this->description = $data['description'];
        if (isset($data['image'])) $this->image = $data['image'];
        if (isset($data['latitude'])) $this->latitude = (float)$data['latitude'];
        if (isset($data['longitude'])) $this->longitude = (float)$data['longitude'];
        if (isset($data['user_id'])) $this->user_id = (int)$data['user_id'];
    }

    // Simple getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): ?int { return $this->user_id; }
    public function getTitle(): ?string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    public function getImage(): ?string { return $this->image; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function getCreatedAt(): ?string { return $this->created_at; }
}

