<?php

namespace App\Models;

use Framework\Core\Model;
use Framework\Core\IIdentity;

/**
 * Simple User value object representing an authenticated user.
 */
class User extends Model implements IIdentity
{
    protected ?int $id = null;
    protected ?string $username = null;
    protected ?string $password = null;
    protected ?string $name = null;


    public function __construct(
        $id = null,
        public string $login = '',
        $name = ''
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Register a new user. Returns null on success, or error message on failure.
     */
    public static function register(string $name, string $username, string $password, string $repeat_password): ?string
    {
        // SECURITY: Validate input lengths and types on server side
        $name = trim((string)$name);
        $username = trim((string)$username);

        if (empty($name) || strlen($name) < 1 || strlen($name) > 255) {
            return "Name must be between 1 and 255 characters";
        }
        if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
            return "Username must be between 3 and 50 characters";
        }
        if (empty($password) || strlen($password) < 8) {
            return "Password must be at least 8 characters long";
        }
        if ($password !== $repeat_password) {
            return "Passwords do not match";
        }

        $user = self::getAll('`username` like ?', [$username]);
        if ($user) {
            return "Username already taken";
        }

        $user = new self();
        $user->setName($name);
        // SECURITY: Hash password using bcrypt
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
        $user->setUsername($username);
        try {
            $user->save();
            $_SESSION['user'] = $user;
        } catch (\Exception $e) {
            return "Registration failed (server error)";
        }
        return null;
    }

    /**
     * Edit user account. Returns null on success, or error message on failure.
     */
    public static function edit(int $userId, ?string $name, ?string $username, ?string $password): ?string
    {
        $user = self::getOne($userId);
        if (!$user) {
            return "User not found (this should not happen)";
        }
        $numberOfChanges = 0;
        if ($name) {
            // SECURITY: Validate name length on server side
            $name = trim((string)$name);
            if (empty($name) || strlen($name) < 1 || strlen($name) > 255) {
                return "Name must be between 1 and 255 characters";
            }
            $user->setName($name);
            $numberOfChanges++;
        }
        if ($username && $username != $user->getUsername()) {
            // SECURITY: Validate username length on server side
            $username = trim((string)$username);
            if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
                return "Username must be between 3 and 50 characters";
            }
            $others = self::getAll('`username` like ?', [$username]);
            if ($others) {
                return "Username already taken";
            }
            $user->setUsername($username);
            $numberOfChanges++;
        }
        if ($password) {
            // SECURITY: Validate and hash password on server side
            if (strlen($password) < 8) {
                return "Password must be at least 8 characters long";
            }
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
            $numberOfChanges++;
        }
        try {
            $user->save();
            $_SESSION['user'] = $user;
        } catch (\Exception $e) {
            return "Edit failed (server error)";
        }
        if ($numberOfChanges === 0) {
            return "No changes made.";
        }
        return null;
    }

    /**
     * Delete user account. Returns null on success, or error message on failure.
     * This cascades deletion of all related posts, comments, and likes.
     */
    public static function deleteAccount(int $userId): ?string
    {
        $user = self::getOne($userId);
        if (!$user) {
            return "User not found (this should not happen)";
        }
        try {
            // Get all posts by this user to delete them properly (with cascade)
            $userPosts = Post::getAll('user_id = ?', [$userId]);
            if (is_array($userPosts) && !empty($userPosts)) {
                foreach ($userPosts as $post) {
                    Post::deleteById($post->getId());
                }
            }

            // Delete all comments created by this user (on other users' posts)
            Comment::deleteWhere('user_id = ?', [$userId]);

            // Delete all likes created by this user
            Like::deleteWhere('user_id = ?', [$userId]);

            // Finally, delete the user account
            $user->delete();
            $_SESSION['user'] = null;
        } catch (\Exception $e) {
            return "Deletion failed (server error): " . $e->getMessage();
        }
        return null;
    }

}
