<?php

namespace Geekbrains\Application1\Domain\Controllers;
use Geekbrains\Application1\Application\Render;

class PageController extends Controller {

    public function actionIndex() {
        $render = new Render();
        
        return $render->renderPage('page-index.twig', ['title' => 'Главная страница']);
    }
}