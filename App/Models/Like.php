<?php

namespace App\Models;

use Framework\Core\Model;
use Exception;

class Like extends Model
{
    protected static ?string $tableName = 'likes';

    protected ?int $id = null;
    protected ?int $user_id = null;
    protected ?string $target_type = null;
    protected ?int $target_id = null;
    protected ?string $created_at = null;

    // Toggle like: if exists remove, otherwise create. Returns true if liked, false if unliked.
    public static function toggleLike(int $userId, string $targetType, int $targetId): bool
    {
        // Check if exists
        $rows = self::executeRawSQL("SELECT id FROM `" . self::getTableName() . "` WHERE user_id=? AND target_type=? AND target_id=?", [$userId, $targetType, $targetId]);
        if (!empty($rows)) {
            // Remove
            $id = $rows[0]['id'];
            $model = self::getOne($id);
            if ($model) { $model->delete(); }
            return false;
        }
        // Create
        $like = new self();
        $like->user_id = $userId;
        $like->target_type = $targetType;
        $like->target_id = $targetId;
        $like->created_at = date('Y-m-d H:i:s');
        $like->save();
        return true;
    }

    public static function countForTarget(string $targetType, int $targetId): int
    {
        return self::getCount('target_type = ? AND target_id = ?', [$targetType, $targetId]);
    }

    public static function userHasLiked(int $userId, string $targetType, int $targetId): bool
    {
        $cnt = self::getCount('user_id = ? AND target_type = ? AND target_id = ?', [$userId, $targetType, $targetId]);
        return $cnt > 0;
    }

    // New: delete all likes for a specified target (e.g. all likes for a post or a comment)
    public static function deleteByTarget(string $targetType, int $targetId): int
    {
        try {
            $sql = "DELETE FROM `" . self::getTableName() . "` WHERE target_type = ? AND target_id = ?";
            $stmt = \Framework\DB\Connection::getInstance()->prepare($sql);
            $stmt->execute([$targetType, $targetId]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }

    // Simple getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): ?int { return $this->user_id; }
    public function getTargetType(): ?string { return $this->target_type; }
    public function getTargetId(): ?int { return $this->target_id; }
    public function getCreatedAt(): ?string { return $this->created_at; }
}
