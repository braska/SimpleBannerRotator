<?php

namespace App\Controllers;

use Phalcon\Mvc\View;

class ErrorController extends ControllerBase
{

    public function notFoundAction() {
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $this->view->pick('error/error');
        \Phalcon\Tag::prependTitle('404');
        $this->view->title = "404 - страница не найдена";
        $this->response->setStatusCode(404, 'Not Found');
    }

    public function uncaughtExceptionAction() {
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $this->view->pick('error/error');
        \Phalcon\Tag::prependTitle('500');
        $this->view->title = "500 - внутренняя ошибка";
        $this->response->setStatusCode(500, 'Internal Server Error');
    }
}