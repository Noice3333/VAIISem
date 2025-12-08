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

    public function edit(string $name, string $username, string $password): int
    {
        $userId = $_SESSION['user']->getId();
        $user = User::getOne($userId);
        $special = false;
        $numberOfChanges = 0;
        if ($user) {
            if ($name) {
                $user->setName($name);
                $numberOfChanges++;
            }
            if ($username && $username != $_SESSION['user']->getUsername()) {
                $others = User::getAll('`username` like ?',[$username]);
                if (!$others) {
                    $user->setUsername($username);
                    $numberOfChanges++;
                } else {
                    $special = true;
                }
            }
            if ($password) {
                $user->setPassword($password);
                $numberOfChanges++;
            }
            try {
                $user->save();
                $_SESSION['user'] = $user;
            } catch (\Exception $e) {
                return -2;
            }
        } else {
            return -1;
        }
        if ($special) {
            return -3;
        }
        if ($numberOfChanges > 0) {
            return 0;
        } else {
            return 1;
        }

    }

    public function delete() : bool
    {
        $userId = $_SESSION['user']->getId();
        $user = User::getOne($userId);
        try {
            $user->delete();
            $_SESSION['user'] = null;
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}