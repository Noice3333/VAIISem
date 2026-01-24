<?php

namespace App\Auth;

use App\Models\User;
use Framework\Auth\DummyAuthenticator;

class GraffitiAuth extends DummyAuthenticator
{
    public function login(string $username, string $password): bool
    {
        $user = User::getAll('`username` like ?',[$username]);
        if ($user && $user[0]->getPassword() === $password) {
            $_SESSION['user'] = $user[0];
            return true;
        }
        return false;
    }


}