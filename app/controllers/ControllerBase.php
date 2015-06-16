<?php

namespace App\Controllers;

use Phalcon\Mvc\View;

class ControllerBase extends \Phalcon\Mvc\Controller
{

    protected function initialize()
    {
        $this->view->setLayout('common');
        if ($this->request->isAjax() == true) {
            $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
        }
        $this->assets
            ->collection('css')
            ->addCss('css/bootstrap.min.css')
            ->addCss('css/font-awesome.min.css')
            ->addCss('css/style.css')
            ->addCss('css/widgets.css')
            ->addCss('css/custom.css');
        $this->assets
            ->collection('head-js')
            ->addJs('js/respond.min.js')
            ->addJs('js/html5shiv.js');
        $this->assets
            ->collection('bottom-js')
            ->addJs('js/jquery.js')
            ->addJs('js/bootstrap.min.js')
            ->addJs('js/custom.js');
        \Phalcon\Tag::setTitle(' :: '.$this->vars->sitename);
        $this->view->description = '';
        $this->view->keywords = '';
    }
}