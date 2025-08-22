<?php

namespace Core;

class Auth
{
    public static function user()
    {
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user'];
        }
        return null;
    }

    public static function check()
    {
        return isset($_SESSION['user_id']);
    }

    public static function id()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function attempt($credentials)
    {
        $db = Database::getInstance();
        
        $user = $db->fetch(
            "SELECT * FROM users WHERE email = ? LIMIT 1",
            [$credentials['email']]
        );

        if ($user && password_verify($credentials['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
            return true;
        }

        return false;
    }

    public static function login($user)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
    }

    public static function logout()
    {
        session_destroy();
        session_start();
    }

    public static function guest()
    {
        return !self::check();
    }
}