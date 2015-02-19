<?php
namespace App\Controllers\Admin;

use Phalcon\Tag;

class RobotsController extends ControllerBase {

    public function indexAction() {
        if($this->request->getPost('txt')){
            $content = $this->request->getPost('txt');
            copy('robots.txt', 'robots.txt.bak');
            file_put_contents('robots.txt', $content);
            $this->flashSession->success("Файл успешно сохранён");
        }
        $txt = file_get_contents('robots.txt');
        $this->view->txt = $txt;
        $this->view->title="Редактирование файла robots.txt";
        Tag::prependTitle("Редактирование файла robots.txt");
    }

}