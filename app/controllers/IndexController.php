<?php

namespace App\Controllers;

use Phalcon\Mvc\View;

class IndexController extends ControllerBase
{

    public function indexAction(){}

    public function loginAction()
    {
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        if($this->router->getMatchedRoute()->getName() != 'auth')
            $this->dispatcher->forward(array("controller" => "error", "action" => "notFound"));
        \Phalcon\Tag::prependTitle('Вход');
        $this->view->title = "Вход";
        if($this->auth->logged_in())
            return $this->response->redirect("");
        if($this->request->isPost())
        {
            $auth = $this->auth->login($this->request->getPost('username'), $this->request->getPost('password'), (bool)$this->request->get('remember'));
            if ($auth)
                return $this->response->redirect("");
            else
                $this->flashSession->error(':(');
        }
        /*$user=new \App\Models\Users();
        $user->fname='Тест';
        $user->lname='Тестов';
        $user->thname='';
        $user->email='djdisc@mail.ru';
        $user->password='4444';
        if(!$user->create())
            foreach($user->getMessages() as $message) {
                $this->flashSession->error($message->getMessage());
            }
        else{
            $role = new \App\Models\RolesUsers();
            $role->user_id = $user->id;
            $role->role_id = \App\Models\Roles::findFirst(array('name="login"'))->id;
            $role->create();
            $role->role_id = \App\Models\Roles::findFirst(array('name="admin"'))->id;
            $role->create();
        }*/

        /*$admin = \App\Models\Users::findFirst(1);
        $admin->password = $this->auth->hash('ghfdbkj');
        $admin->save();*/
    }

    public function logoutAction() {
        if($this->router->getMatchedRoute()->getName() != 'auth')
            $this->dispatcher->forward(array("controller" => "error", "action" => "notFound"));
        $this->auth->logout();
        return $this->response->redirect('');
    }
}