<?php

namespace App\Controllers\Advertiser;


class IndexController extends ControllerBase {
    public function indexAction() {
        return $this->response->redirect(array('for'=>'controller', 'controller'=>'banners'));
    }
}