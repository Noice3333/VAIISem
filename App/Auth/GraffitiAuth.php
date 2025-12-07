<?php

namespace App\Auth;

use Framework\Auth\DummyAuthenticator;

class GraffitiAuth extends DummyAuthenticator
{
    public function login(string $username, string $password): bool
    {
        if ($username == 'ye' && $password == 'ye') {
            $_SESSION['user'] = $username;
            return true;
        }
        return false;
    }

    public function register(string $username, string $password,
        string $repeat_password): bool
    {
        $result = $password == $repeat_password;
        if ($result) {
            $_SESSION['user'] = $username;
        }

        return $result;
    }
}