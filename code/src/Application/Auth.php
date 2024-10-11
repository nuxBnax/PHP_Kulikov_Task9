<?php

namespace Geekbrains\Application1\Application;

class Auth
{
    public static function getPasswordHash(string $rawPassword): string
    {
        return password_hash($rawPassword, PASSWORD_BCRYPT);
    }

    public static function RestoreSession(): bool
    {
        $login = $_COOKIE['login'] ?? null;
        if ($login !== null && !isset($_SESSION['user_name'])) {
            if(Auth::verifyToken($_POST['login'], $login)) {
               $result = Application::$auth->proceedAuth($login, $_POST['password']);
               var_dump($result);
               return $result;
               
            }
        }
        return false;
    }
    

    public function proceedAuth(string $login, string $password): bool
    {
        
        $sql = "SELECT id_user, user_name, user_lastname, password_hash FROM users WHERE user_login = :user_login";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['user_login' => $login]);
        $result = $handler->fetchAll();

        if (!empty($result) && password_verify($password, $result[0]['password_hash'])) {
            $_SESSION['user_name'] = $result[0]['user_name'];
            $_SESSION['user_lastname'] = $result[0]['user_lastname'];
            $_SESSION['id_user'] = $result[0]['id_user'];

            return true;
        } else {
            return false;
        }
    }

    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(16));

        return $token;
    }

    public static function verifyToken(string $login, string $token): bool
    {
        $sql = "SELECT token FROM users WHERE login = :login";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['login' => $login]);
        $result = $handler->fetchAll();
        $tokenFromDB = $result[0]['token'];
        if ($token === $tokenFromDB) {
            return true;
        }
        return false;
    }
    
}