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
        $user = self::getAll('`username` like ?', [$username]);
        if ($user) {
            return "Username already taken";
        }
        if ($password !== $repeat_password) {
            return "Passwords do not match";
        }
        $user = new self();
        $user->setName($name);
        $user->setPassword($password);
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
            $user->setName($name);
            $numberOfChanges++;
        }
        if ($username && $username != $user->getUsername()) {
            $others = self::getAll('`username` like ?', [$username]);
            if ($others) {
                return "Username already taken";
            }
            $user->setUsername($username);
            $numberOfChanges++;
        }
        if ($password) {
            $user->setPassword($password);
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
     */
    public static function deleteAccount(int $userId): ?string
    {
        $user = self::getOne($userId);
        if (!$user) {
            return "User not found (this should not happen)";
        }
        try {
            $user->delete();
            $_SESSION['user'] = null;
        } catch (\Exception $e) {
            return "Deletion failed (server error)";
        }
        return null;
    }

}
