<?php

namespace App\Models;

use Framework\Core\Model;
use Framework\Http\Request;
use Exception;

class Comment extends Model
{
    protected static ?string $tableName = 'comments';

    protected ?int $id = null;
    protected ?int $post_id = null;
    protected ?int $user_id = null;
    protected ?string $content = null;
    protected ?string $created_at = null;

    public static function create(array $data): self
    {
        $c = new self();
        if (isset($data['post_id'])) $c->post_id = (int)$data['post_id'];
        if (isset($data['user_id'])) $c->user_id = (int)$data['user_id'];
        if (isset($data['content'])) $c->content = $data['content'];
        $c->created_at = date('Y-m-d H:i:s');
        $c->save();
        return $c;
    }

    public static function getForPost(int $postId, ?int $limit = null, ?int $offset = null): array
    {
        $order = 'created_at DESC';
        return self::getAll('post_id = ?', [$postId], $order, $limit, $offset);
    }

    public static function deleteById(int $id): bool
    {
        $c = self::getOne($id);
        if ($c === null) return false;

        // Delete all likes on this comment first
        try {
            Like::deleteByTarget('comment', $id);
        } catch (\Throwable $e) { /* ignore */ }

        $c->delete();
        return true;
    }

    // New: delete all comments for a post and return how many were deleted
    public static function deleteByPost(int $postId): int
    {
        try {
            $sql = "DELETE FROM `" . self::getTableName() . "` WHERE post_id = ?";
            $stmt = \Framework\DB\Connection::getInstance()->prepare($sql);
            $stmt->execute([$postId]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }

    // Simple getters
    public function getId(): ?int { return $this->id; }
    public function getPostId(): ?int { return $this->post_id; }
    public function getUserId(): ?int { return $this->user_id; }
    public function getContent(): ?string { return $this->content; }
    public function getCreatedAt(): ?string { return $this->created_at; }
}
