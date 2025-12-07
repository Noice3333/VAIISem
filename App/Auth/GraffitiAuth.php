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

    public function register(string $name, string $username, string $password,
        string $repeat_password): int
    {
        $user = User::getAll('`username` like ?',[$username]);
        if (!$user) {
            $result = $password == $repeat_password;
            if ($result) {
                $user = new User();
                $user->setName($name);
                $user->setPassword($password);
                $user->setUsername($username);
                try {
                    $user->save();
                    $_SESSION['user'] = $user;
                } catch (\Exception $e) {
                    return -3;
                }
            } else {
                return -2;
            }
        } else {
            return -1;
        }
        return 0;
    }
}