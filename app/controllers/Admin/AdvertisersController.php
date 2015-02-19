<?php

namespace App\Controllers\Admin;


use App\Models\Users;
use Phalcon\Tag;

class AdvertisersController extends ControllerBase {
    public function indexAction() {
        Tag::prependTitle('Рекламодатели');
        $this->view->title = "Рекламодатели";
        $this->view->advertisers = Users::find(array('type="advertiser"'));
    }

    public function addAction() {
        $user = new Users();
        if ($this->request->isPost())
        {
            $data = $this->request->getPost();
            $data['type'] = "advertiser";
            $messages = $user->signup($data);
            if(count($messages) && $messages !== true){
                foreach($messages as $message) {
                    $this->flashSession->error($message->getMessage());
                }
            } else {
                $this->flashSession->success("Рекламодатель добавлен");
                return $this->response->redirect(array('for'=>'controller', 'controller'=>'advertisers'));
            }
        }
        $this->view->user = $user;
        $this->view->title="Добавление рекламодателя";
        \Phalcon\Tag::prependTitle("Добавление рекламодателя");
    }

    public function profileAction() {
        $id = $this->dispatcher->getParam('id');
        $user = Users::findFirst($id);
        if ($user && $id)
        {
            if ($this->request->isPost())
            {
                $action = $this->request->getPost('action');
                if($action == "change_info") {
                    if($user->update($this->request->getPost(), array('fname', 'lname', 'thname'))) {
                        $this->auth->refresh_user();
                        $this->flashSession->success("Информация о рекламодателе обновлена");
                    } else {
                        foreach($user->getMessages() as $message) {
                            $this->flashSession->error($message->getMessage());
                        }
                    }
                } elseif($action == "change_email") {
                    if($user->update($this->request->getPost(), array('email'))) {
                        $this->auth->refresh_user();
                        $this->flashSession->success("E-mail рекламодателя изменён");
                    } else {
                        foreach($user->getMessages() as $message) {
                            $this->flashSession->error($message->getMessage());
                        }
                    }
                } elseif($action == "change_password") {
                    if($this->request->getPost('password') === $this->request->getPost('password_confirm')) {
                        if($user->update(array('password'=>$this->auth->hash($this->request->getPost('password'))))) {
                            $this->auth->refresh_user();
                            $this->flashSession->success('Пароль рекламодателя успешно изменён');
                        }
                        else
                            foreach ($user->getMessages() as $message)
                                $this->flashSession->error($message->getMessage());
                    }
                    else {
                        $this->flashSession->error('Пароль и повтор пароля должны совпадать.');
                    }
                }
            }


            $this->view->user = $user;
            $this->view->title = $user->getUsername()." - Управление";
            \Phalcon\Tag::prependTitle($user->getUsername()." - Управление");
        } else $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
    }
}