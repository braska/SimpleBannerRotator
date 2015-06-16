<?php

namespace App\Controllers\Advertiser;

use Phalcon\Mvc\View;

class ControllerBase extends \App\Controllers\ControllerBase
{

    public function beforeExecuteRoute($dispatcher)
    {
        //var_dump('das');exit;
        $this->view->setViewsDir(APPLICATION_PATH.$this->di->getConfig()->app->viewsDir.'advertiser/');
        $this->view->setPartialsDir('partials/');
        $this->view->setLayoutsDir('../layouts/');
        $this->view->setMainView('../index');
    }
}