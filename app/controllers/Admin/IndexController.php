<?php

namespace App\Controllers\Admin;


use Phalcon\Tag;

class IndexController extends ControllerBase {
    public function indexAction() {
        return $this->response->redirect(array('for'=>'controller', 'controller'=>'banners'));
    }
}