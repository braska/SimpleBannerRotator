<?php

namespace App\Library;

use Phalcon\Events\Event,
    Phalcon\Mvc\Dispatcher,
    Phalcon\Mvc\User\Plugin;


class Zonner extends Plugin {
    public function beforeDispatchLoop(Event $event, Dispatcher $dispatcher) {
        if (!$this->auth->logged_in() && $this->router->getActionName()!='login' && $this->router->getControllerName()!='rotator') {
            $dispatcher->setReturnedValue($this->response->redirect('login'));
            return false;
        }

        if($this->router->getControllerName()!='rotator') {
            if ($this->auth->logged_in('admin'))
                $dispatcher->setDefaultNamespace('App\Controllers\Admin');
            elseif ($this->auth->logged_in('advertiser'))
                $dispatcher->setDefaultNamespace('App\Controllers\Advertiser');
        }
        return true;
    }
 }