<?php

namespace App\Auth;

use App\Models\User;
use Framework\Auth\DummyAuthenticator;

class GraffitiAuth extends DummyAuthenticator
{
    public function login(string $username, string $password): bool
    {
        // SECURITY: Validate input before querying database
        $username = trim((string)$username);
        if (empty($username) || strlen($username) > 50) {
            return false;
        }

        $user = User::getAll('`username` like ?', [$username]);
        // SECURITY: Use password_verify for safe bcrypt comparison
        if ($user && password_verify($password, $user[0]->getPassword())) {
            $_SESSION['user'] = $user[0];
            return true;
        }
        return false;
    }


}