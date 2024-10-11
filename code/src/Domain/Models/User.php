<?php

namespace Geekbrains\Application1\Domain\Models;

use Geekbrains\Application1\Application\Application;
use Geekbrains\Application1\Application\Auth;
use Geekbrains\Application1\Infrastructure\Storage;

class User
{

    private ?string $userName;

    private ?string $userLastName;
    private ?int $userBirthday;

    private ?int $userId;

    private ?string $userLogin;
    private ?string $userPassword;
    private static string $storageAddress = '/storage/birthdays.txt';

    public function __construct(string $name = null, string $lastName = null, int $birthday = null, int $userId = null)
    {
        $this->userName = $name;
        $this->userLastName = $lastName;
        $this->userBirthday = $birthday;
        $this->userId = $userId;
    }

    public function setName(string $userName): void
    {
        $this->userName = $userName;
    }

    public function setLastName(string $userLastName): void
    {
        $this->userLastName = $userLastName;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }
    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getUserLastName(): string
    {
        return $this->userLastName;
    }

    public function getUserBirthday(): ?int
    {
        return $this->userBirthday;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserIdByLogin(string $login): string
    {
        $sql = "SELECT id_user FROM users WHERE user_login = :user_login";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['user_login' => $login]);
        $result = $handler->fetch();

        return $result;
    }

    public function setBirthdayFromString(string $birthdayString): void
    {
        $this->userBirthday = strtotime($birthdayString);
    }

    public static function getAllUsersFromStorage(?int $limit = null): array
    {
        $sql = "SELECT * FROM users";
        
        if(isset($limit) && $limit > 0) {
            $sql .= " WHERE id_user > " .(int)$limit;
        }

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute();
        $result = $handler->fetchAll();

        $users = [];

        foreach ($result as $item) {
            $user = new User($item['user_name'], $item['user_lastname'], $item['user_birthday_timestamp'], $item['id_user']);
            $users[] = $user;
        }

        return $users;
    }

    public static function getUserFromStorageById(int $id): User
    {
        $sql = "SELECT * FROM users WHERE id_user = :id";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['id' => $id]);
        $result = $handler->fetch();

        return new User($result['user_name'], $result['user_lastname'], $result['user_birthday_timestamp'], $result['id_user']);
    }

    public static function validateRequestData(): bool
    {
        $result = true;

        if (
            !(
                isset($_POST['name']) && !empty($_POST['name']) &&
                isset($_POST['lastname']) && !empty($_POST['lastname']) &&
                isset($_POST['birthday']) && !empty($_POST['birthday']) &&
                isset($_POST['login']) && !empty($_POST['login']) &&
                isset($_POST['password']) && !empty($_POST['password'])
            )
        ) {
            $result = false;
        }    

        if (preg_match('/<([^>]+)>/', $_POST['name'])) {
            $result = false;
        }

        if (preg_match('/<([^>]+)>/', $_POST['lastname'])) {
            $result = false;
        }
      
        if(!preg_match('/^(\d{2}-\d{2}-\d{4})$/', $_POST['birthday'])){
            $result =  false;
        }
        
        return $result;
    }

    public function setParamsFromRequestData(): void
    {
        $this->userName = htmlspecialchars($_POST['name']);
        $this->userLastName = htmlspecialchars($_POST['lastname']);
        $this->setBirthdayFromString($_POST['birthday']); 
        $this->userLogin = htmlspecialchars($_POST['login']); 
        $this->userPassword = Auth::getPasswordHash($_POST['password']);
    }

    public function saveToStorage()
    {
         $sql = "INSERT INTO users(user_name, user_lastname, user_birthday_timestamp, user_login, password_hash) VALUES (:user_name, :user_lastname, :user_birthday, :user_login, :user_password)";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute([
            'user_name' => $this->userName,
            'user_lastname' => $this->userLastName,
            'user_birthday' => $this->userBirthday,
            'user_login' => $this->userLogin,
            'user_password' => $this->userPassword
        ]);
    }

    public static function exists(int $id): bool
    {
        $sql = "SELECT count(id_user) as user_count FROM users WHERE id_user = :id_user";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute([
            'id_user' => $id
        ]);

        $result = $handler->fetchAll();

        if (count($result) > 0 && $result[0]['user_count'] > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function updateUser(int $userID, array $userDataArray): void
    {
        $sql = "UPDATE users SET ";

        $counter = 0;
        foreach ($userDataArray as $key => $value) {
            $sql .= $key . " = :" . $key;
            //UPDATE users SET user_name = :user_name, user_lastname = :user_lastname WHERE
            if ($counter != count($userDataArray) - 1) {
                $sql .= ",";
            }

            $counter++;
        }
        $sql .= " WHERE id_user = " . $userID . ";";
        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute($userDataArray);
    }

    public static function deleteFromStorage(int $user_id): void
    {
        $sql = "DELETE FROM users WHERE id_user = :id_user";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['id_user' => $user_id]);
    }

    public static function getUserRoleById(array $roles): array
    {

        if (isset($_SESSION['id_user'])) {
            $rolesSql = "SELECT * FROM user_roles WHERE id_user = :id";

            $handler = Application::$storage->get()->prepare($rolesSql);
            $handler->execute(['id' => $_SESSION['id_user']]);
            $result = $handler->fetchAll();

            if (!empty($result)) {
                foreach ($result as $role) {
                    $roles[] = $role['role'];
                }
            }
        }
        return $roles;
    }
    public static function isAdmin(?int $user_id): bool    {
        if ($user_id > 0) {
            $sql = "SELECT role FROM user_roles WHERE role = 'admin' AND id_user = :id_user";

            $handler = Application::$storage->get()->prepare($sql);
            $handler->execute([
                'id_user' => $user_id
            ]);
            $result = $handler->fetchAll();
            if (count($result) > 0 ) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    public static function setToken(string $userLogin): void
    { // установление токена при входе

        $token = Auth::generateToken();
        $sql = "UPDATE users SET csrf_token";

        $sql .= " = :csrf_token" . " WHERE user_login = :user_login;";

        setcookie('user_login', $token, time() + 60 * 60 * 24 * 7, '/');

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(
            [
                'user_login' => $userLogin,
                'csrf_token' => $token
            ]
        );
    }

     public function getUserDataAsArray(): array {
        $userArray = [
            'id' => $this->userId,
            'username' => $this->userName, 
            'userlastname' => $this->userLastName,
            'userbirthday' => date('d.m.Y', $this->userBirthday)
        ];
        return $userArray;
    }
}