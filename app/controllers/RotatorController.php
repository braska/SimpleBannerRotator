<?php

namespace App\Controllers;

use App\Models\Banners;
use App\Models\Views;
use Phalcon\Mvc\View;

class RotatorController extends ControllerBase {
    protected function initialize() {
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

    public function getAction() {
        $url = $this->request->getQuery('url');

        $banners = $this->modelsManager->createBuilder()
            ->from(array('b'=>'App\Models\Banners'))
            ->innerJoin('App\Models\BannersZones', 'b.id = bz.banner_id AND bz.zone_id = ' . $this->request->getQuery('zone_id', 'int'), 'bz')
            ->andWhere('(end_date IS NULL OR end_date > ' . time() . ") AND (start_date IS NULL OR start_date <= " . time() . ") AND active = 1 AND archived = 0")
            ->groupBy('b.id')
            ->getQuery()
            ->execute();
            
        if(count($banners)) {

            $existsNonzeroPriority = false;

            $banners = $banners->filter(function($banner) use (&$existsNonzeroPriority, $url) {
                if((!empty($banner->url_mask) && preg_match($banner->url_mask, $url) == 1) || empty($banner->url_mask)) {
                    if(!empty($banner->max_impressions)) {
                        $q = (empty($banner->start_date) ? '' : ('date >= ' . $banner->start_date)) . (empty($banner->end_date) ? '' : (' AND date < ' . $banner->end_date));
                        $views = $banner->countViews(array($q));
                        if ($views < $banner->max_impressions) {
                            if ($banner->priority != 0)
                                $existsNonzeroPriority = true;
                            return $banner;
                        }
                    } else {
                        if ($banner->priority != 0)
                            $existsNonzeroPriority = true;
                        return $banner;
                    }
                }
            });

            $segments = array();
            $end = 0;
            foreach ($banners as $banner) {
                $priority = $existsNonzeroPriority ? $banner->priority : 1;
                $segments[] = ['banner'=>$banner, 'start'=>$end, 'end'=>$end + $priority];
                $end += $priority;
            }


            $rand = rand(0, $end * 100) / 100;
            foreach($segments as $segment) {
                if($segment['start'] <= $rand && $segment['end'] > $rand) {
                    $banner_selected = $segment['banner'];
                    break;
                }
            }

            if((isset($banner_selected) && empty($banner_selected->id)) || !isset($banner_selected)) return;

            $view = new Views();
            $view->save(array('date'=>time(), 'banner_id'=>$banner_selected->id, 'zone_id'=>$this->request->getQuery('zone_id', 'int')));
            $this->view->view = $view->id;

            if($banner_selected->type == "image")
                $this->view->pick('rotator/image');
            elseif($banner_selected->type == "flash")
                $this->view->pick('rotator/flash');
            elseif($banner_selected->type == "html") {
                $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
                echo $banner_selected->content;
                return;
            }
            $this->view->banner = $banner_selected;
        } else return;
    }

    public function get_jsAction() {
        $this->response->setContentType('text/javascript');
        $this->view->pick('rotator/js');
    }
    
    public function get_mobileAction(){
        $zone_id = $this->request->getQuery('zone_id', 'int');
        $key = $this->request->getQuery(sha1($_SERVER['HTTP_HOST']), 'string');
        
        if(!$zone_id OR $key !== base64_encode(sha1(sha1($_SERVER['HTTP_HOST'])))){
            $this->response->setStatusCode(400, 'Bad request');
            return $this->response->send();
        }

        $banners = $this->modelsManager->createBuilder()
            ->from(array('b'=>'App\Models\Banners'))
            ->leftJoin('App\Models\Views', 'b.id = v.banner_id AND IF(b.start_date IS NULL, 1, IF(v.date >= b.start_date, 1, 0)) = 1 AND IF(b.end_date IS NULL, 1, IF(v.date < b.end_date, 1, 0)) = 1', 'v')
            ->innerJoin('App\Models\BannersZones', 'b.id = bz.banner_id AND bz.zone_id = ' . $this->request->getQuery('zone_id', 'int'), 'bz')
            ->andWhere('(end_date IS NULL OR end_date > ' . time() . ") AND (start_date IS NULL OR start_date <= " . time() . ") AND active = 1 AND archived = 0")
            ->groupBy('b.id')
            ->having('max_impressions IS NULL OR COUNT(v.id) < max_impressions')
            ->getQuery()
            ->execute();
            
        if(count($banners)) {
            $existsNonzeroPriority = false;
            
            if($banner->priority != 0)
                $existsNonzeroPriority = true;

            $segments = array();
            $end = 0;
            foreach ($banners as $banner) {
                $priority = $existsNonzeroPriority ? $banner->priority : 1;
                $segments[] = ['banner'=>$banner, 'start'=>$end, 'end'=>$end + $priority];
                $end += $priority;
            }


            $rand = rand(0, $end * 100) / 100;
            foreach($segments as $segment) {
                if($segment['start'] <= $rand && $segment['end'] > $rand) {
                    $banner_selected = $segment['banner'];
                    break;
                }
            }

            if((isset($banner_selected) && empty($banner_selected->id)) || !isset($banner_selected)) return;

            $view = new Views();
            $view->save(array('date'=>time(), 'banner_id'=>$banner_selected->id, 'zone_id'=>$this->request->getQuery('zone_id', 'int')));
            $this->view->view = $view->id;

            if($banner_selected->type == "image")
                $this->view->pick('rotator/image');
            else if($banner_selected->type == "flash")
                return false;
            else if($banner_selected->type == "html") {
                $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
                echo $banner_selected->content;
                return;
            }
            $this->view->banner = $banner_selected;
        } else return;
    }

    public function clickAction() {
        $id = $this->dispatcher->getParam('id');
        $banner = Banners::findFirst(array('id = :id:', 'bind'=>array('id'=>$id)));
        if($id && $banner) {
            $view_id = $this->request->getQuery('view');
            $view = Views::findFirst(array('id = :id:', 'bind'=>array('id'=>$view_id)));
            $view->update(array('clicked'=>1));
            return $this->response->redirect($banner->link, true, 302);
        } else $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
    }
}