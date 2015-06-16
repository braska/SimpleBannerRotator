<?php
namespace App\Controllers\Admin;

use App\Library\Validators\UploadImage;
use App\Library\Validators\UploadSize;
use App\Library\Validators\UploadType;
use App\Library\Validators\UploadValid;
use App\Models\Banners;
use Phalcon\Mvc\View;

class AjaxController extends ControllerBase {


    public function initialize()
    {
        parent::initialize();
        $this->view->setRenderLevel(View::LEVEL_NO_RENDER);

        if (!$this->request->isAjax())
            $this->dispatcher->forward(array("namespace" => 'App\Controllers', "controller" => "error", "action" => "notFound"));
    }

    public function get_bannersAction() {
        $type = $this->request->getQuery('type');
        $banners = Banners::find(array('type = :type:', 'bind'=>array('type'=>$type)))->toArray();
        $arr = array('banners'=>$banners);
        if($this->request->getQuery('type') == 'image') {
            $arr['directory'] = $this->url->get($this->config->banners->imagePath);
        } elseif($this->request->getQuery('type') == 'flash') {
            $arr['directory'] = $this->url->get($this->config->banners->flashPath);
        }
        echo json_encode($arr);
    }
}