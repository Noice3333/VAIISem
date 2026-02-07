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

    // New: update post by id with provided data. Returns updated model or null when not found.
    public static function updateById(int $id, array $data): ?self
    {
        $post = self::getOne($id);
        if ($post === null) return null;

        // Keep track of old image so we can remove it if replaced
        $oldImage = $post->image;

        if (isset($data['title'])) $post->title = $data['title'];
        if (isset($data['description'])) $post->description = $data['description'];
        if (array_key_exists('image', $data)) $post->image = $data['image'];
        if (isset($data['latitude'])) $post->latitude = (float)$data['latitude'];
        if (isset($data['longitude'])) $post->longitude = (float)$data['longitude'];

        $post->save();

        // If a new image was provided and differs from the old one, attempt to remove the old file
        if (array_key_exists('image', $data)) {
            $newImage = $data['image'];
            if ($newImage && $oldImage && $newImage !== $oldImage) {
                self::removeImageFile($oldImage);
            }
        }

        return $post;
    }

    // New: delete post by id. Returns true when deleted, false when not found.
    public static function deleteById(int $id): bool
    {
        $post = self::getOne($id);
        if ($post === null) return false;

        // Capture image path and delete the record first (so Model's delete works as usual)
        $image = $post->image;
        $post->delete();

        // Remove image file
        if ($image) {
            self::removeImageFile($image);
        }

        return true;
    }

    /**
     * Safely remove an image file stored under public/uploads.
     * Only removes files that resolve into the project's public/uploads directory to avoid accidental deletions.
     */
    private static function removeImageFile(string $imagePath): void
    {
        // Expect stored images to use a path like '/uploads/filename.ext' or 'uploads/filename.ext'
        $trimmed = ltrim($imagePath, '\\/');
        // Only allow removal if the path starts with 'uploads/' to avoid removing arbitrary files
        if (stripos($trimmed, 'uploads/') !== 0) {
            return;
        }

        // Resolve to filesystem path inside project public dir
        $projectRoot = dirname(__DIR__, 2);
        $fullPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed);

        // Normalize path and verify it's inside public/uploads
        $realFull = @realpath($fullPath);
        $uploadsDir = realpath($projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads');
        if ($realFull === false || $uploadsDir === false) return;

        // Ensure the file is within the uploads directory
        if (strpos($realFull, $uploadsDir) !== 0) return;

        // Delete if exists and is a file
        if (is_file($realFull)) {
            @unlink($realFull);
        }
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

