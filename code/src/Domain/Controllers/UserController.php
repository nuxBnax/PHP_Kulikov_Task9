<?php

namespace Geekbrains\Application1\Domain\Controllers;

use Geekbrains\Application1\Application\Application;
use Geekbrains\Application1\Application\Render;
use Geekbrains\Application1\Application\Auth;
use Geekbrains\Application1\Domain\Models\User;

class UserController extends AbstractController
{

    protected array $actionsPermissions = [
        'actionHash' => ['admin', 'some'],
        'actionSave' => ['admin'],
    ];

    public function actionIndex()
    {
        $users = User::getAllUsersFromStorage();

        $render = new Render();

        if (!$users) {
            return $render->renderPage(
                'user-empty.twig',
                [
                    'title' => 'Список пользователей в хранилище',
                    'message' => "Список пуст или не найден"
                ]
            );
        } else {
            return $render->renderPage(
                'user-index.twig',
                [
                    'title' => 'Список пользователей в хранилище',
                    'users' => $users,
                    'isAdmin' => User::isAdmin($_SESSION['id_user'] ?? null)
                ]
            );
        }
    }
    public function actionIndexRefresh(){
        $limit = null;
        
        if(isset($_POST['maxId']) && ($_POST['maxId'] > 0)){
            $limit = $_POST['maxId'];
        }

        $users = User::getAllUsersFromStorage($limit);
        $usersData = [];

        if(count($users) > 0) {
            foreach($users as $user){
                $usersData[] = $user->getUserDataAsArray();
            }
        }

        return json_encode($usersData);
    }
    public function actionIndexDelete(): bool|string
    {
               
        $result = [
            'id' => $_POST['id']
        ];
        return json_encode($result);
    }
    public function actionSave(): string
    {
        if (User::validateRequestData()) {
            $user = new User();
            $user->setParamsFromRequestData();
            $user->saveToStorage();
            $render = new Render();

            return $render->renderPage(
                'user-created.twig',
                [
                    'title' => 'Пользователь создан',
                    'message' => "Создан пользователь " . $user->getUserName() . " " . $user->getUserLastName(),
                    'isAdmin' => User::isAdmin($_SESSION['id_user'] ?? null)
                ]
            );
        } else {
            $render = new Render();
            return $render->renderPageWithForm(
                'user-index.twig',
                [
                    'title' => "Введены некорректные данные"
                ]
            );
        }
    }

    public function actionEdit(): string
    {
        $render = new Render();

        return $render->renderPageWithForm(
            'user-create.twig',
            [
                'title' => 'Форма создания пользователя'
            ]
        );
    }

    public function actionUpdate(): string
    {
        if (User::validateRequestData()) {
            $arrayData = [];

     
            if (isset($_POST['name'])) {
                $arrayData['user_name'] = $_POST['name'];
            }
            if (isset($_POST['lastname'])) {
                $arrayData['user_lastname'] = $_POST['lastname'];
            }
            if (isset($_POST['login'])) {
                $arrayData['user_login'] = $_POST['login'];
            }
            if (isset($_POST['password'])) {
                $arrayData['password_hash'] = password_hash('123', PASSWORD_DEFAULT);;
            }

            if (isset($_POST['birthday'])) {
                $arrayData['user_birthday_timestamp'] = strtotime($_POST['birthday']);

            }

            User::updateUser($_GET['id'], $arrayData);
 
            $str = 'Данные пользователя ' . $_POST['name'] . ' обновлены';

           
            $render = new Render();
            return $render->renderPageWithForm(
                'user-created.twig',
                [
                    'message' => $str
                ]
            );
            
           
        } else {

            $render = new Render();
            return $render->renderPageWithForm(
                'user-index.twig',
                [
                    'title' => "Произошла какая-то ошибка"
                ]
            );
        }
    }

    public function actionChange(): string
    {

        $render = new Render();
        return $render->renderPage(
            'user-update.twig',
            [
                'title' => 'Пользователь обновлен',
                'message' => "Обновлен пользователь ",
                'userId' => $_GET['id']
            ]
        );

    }

    public function actionDelete(): string
    {
        if (User::exists($_GET['id'])) {
            User::deleteFromStorage($_GET['id']);
        
            header('Location: /user');
            return "";
        } else {
            throw new \Exception("Пользователь не существует");
        }
    }

    public function actionAuth(): string
    {
        $render = new Render();

        return $render->renderPageWithForm(
            'user-auth.twig',
            [
                'title' => 'Форма логина'
            ]
        );
    }

    public function actionHash(): string
    {
        return Auth::getPasswordHash($_GET['pass_string']);
    }
    public function actionLogin(): string
    {
        $result = false;


        if (isset($_POST['user_login']) && isset($_POST['password'])) {
            $result = Application::$auth->proceedAuth($_POST['user_login'], $_POST['password']);

            if (isset($_POST['user-remember'])) {
                Auth::generateToken();
                User::setToken($_POST['user_login']);
            }
           
        }
        
        if (!$result) {
            $render = new Render();

            return $render->renderPageWithForm(
                'user-auth.twig',
                [
                    'title' => 'Форма логина',
                    'auth_success' => false,
                    'auth_error' => 'Неверные логин или пароль'
                ]
            );
        } else {
            header('Location: /');
            return "";
        }
    }
    public function actionLogout(): void
    {
        session_destroy();
        unset($_SESSION['user_name']);
        unset($_SESSION['user_id']);
        unset($_SESSION['user_lastname']);
        unset($_SESSION['csrf_token']);

        header("Location: /");
        die();
    }
}