<?php

namespace App\Controllers\Admin;


use App\Models\Zones;
use Phalcon\Tag;

class ZonesController extends ControllerBase {
    public function indexAction() {
        Tag::prependTitle('Зоны');
        $this->view->title = "Зоны";
        $this->view->zones = Zones::find();
        
        $this->assets->collection('bottom-js')->addJs('js/zones.js');
    }

    public function addAction() {
        $zone = new Zones();
        if ($this->request->isPost())
        {
            if($zone->save($this->request->getPost(), array('name')))
            {
                $this->flashSession->success("Зона успешно создана");
                return $this->response->redirect(array('for'=>'controller', 'controller'=>'zones'));
            }
            else {
                foreach($zone->getMessages() as $message) {
                    $this->flashSession->error($message->getMessage());
                }
            }
        }
        $this->view->zone = $zone;
        $this->view->pick("zones/edit");

        $this->view->title="Создание новой зоны";
        \Phalcon\Tag::prependTitle("Создание новой зоны");
    }

    public function deleteAction() {
        $id = $this->dispatcher->getParam('id');
        $zone = Zones::findFirst($id);
        if ($zone && $id)
        {
            if($this->request->getQuery('confirm') == "true") {
                $zone->delete();
                $this->flashSession->success("Зона удалена");
                return $this->response->redirect(array('for'=>'controller', 'controller'=>'zones'));
            } elseif($this->request->getQuery('confirm')) {
                $this->flashSession->notice("Удаление зоны отменено");
                return $this->response->redirect(array('for'=>'controller', 'controller'=>'zones'));
            }

            $this->view->zone = $zone;
            $this->view->title="Удаление зоны";
            \Phalcon\Tag::prependTitle("Удаление зоны");
        }
        else $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
    }

    public function editAction() {
        $id = $this->dispatcher->getParam('id');
        $zone = Zones::findFirst($id);
        if ($zone && $id)
        {
            if ($this->request->isPost())
            {
                if($zone->save($this->request->getPost(), array('name')))
                {
                    $this->flashSession->success("Зона отредактирована");
                    return $this->response->redirect(array('for'=>'controller', 'controller'=>'zones'));
                }
                else {
                    foreach($zone->getMessages() as $message) {
                        $this->flashSession->error($message->getMessage());
                    }
                }

            }
            $this->view->zone = $zone;
            $this->view->title="Редактирование зоны";
            \Phalcon\Tag::prependTitle("Редактирование зоны");
        }
        else $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
    }
}