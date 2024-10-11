<?php

namespace Geekbrains\Application1\Application;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class Render {

    private string $viewFolder = '/src/Domain/Views/';
    private FilesystemLoader $loader;
    private Environment $environment;


    public function __construct(){
        $this->loader = new FilesystemLoader($_SERVER['DOCUMENT_ROOT'] . "/../" . $this->viewFolder);
        $this->environment = new Environment($this->loader, [
            //'cache' => $_SERVER['DOCUMENT_ROOT'] . '/../cache/',
        ]);
    }

    public function renderPage(string $contentTemplateName = 'page-index.twig',  array $templateVariables = []) {
        $template = $this->environment->load('main.twig');
        
        $templateVariables['content_template_name'] = $contentTemplateName;
        $templateVariables['pageTitle'] = 'Task 9';
        $templateVariables['time'] = 'Время: ' . date('h:i:s');
        
        $templateVariables['access_template_name'] = 'auth-template.twig';
        if(isset($_SESSION['user_name'])){
            $templateVariables['user_authorized'] = true;
            $templateVariables['user_name'] = $_SESSION['user_name'];
        }


        return $template->render($templateVariables);
    }

    public function renderPageWithForm(string $contentTemplateName = 'page-index.twig', array $templateVariables = []) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        
        $templateVariables['csrf_token'] = $_SESSION['csrf_token'];
 
        return $this->renderPage($contentTemplateName, $templateVariables);
    }

    public function renderPartial(string $contentTemplateName, array $templateVariables = []): string {
        $template = $this->environment->load($contentTemplateName);
        
        if(isset($_SESSION['user_name'])){
            $templateVariables['user_authorized'] = true;
        }
 
        return $template->render($templateVariables);
    }
}