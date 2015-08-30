<?php

namespace App\Controllers;

use App\Models\Banners;
use App\Models\Views;
use App\Models\Zones;
use Phalcon\Mvc\View;

class RotatorController extends ControllerBase {
    protected function initialize()
    {
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

    public function getAction() {
        $url = $this->request->getQuery('url');
        
        if($this->request->has('type'))
            $type = $this->request->get('type');
        else
            $type = 'standart';
            
        if($type === 'mobile'){
            if(!$this->request->has(sha1($_SERVER['HTTP_HOST'])) OR $this->request->get(sha1($_SERVER['HTTP_HOST'])) !== Zones::mobile_secret_key()) {
                $this->response->setStatusCode(400, 'Bad request');
                return $this->response->send();
            }
        }
    
        $banners_sql = $this->modelsManager->createBuilder()
            ->from(array('b'=>'App\Models\Banners'))
            ->innerJoin('App\Models\BannersZones', 'b.id = bz.banner_id AND bz.zone_id = ' . $this->request->getQuery('zone_id', 'int'), 'bz')
            ->andWhere('(end_date IS NULL OR end_date > ' . time() . ") AND (start_date IS NULL OR start_date <= " . time() . ") AND active = 1 AND archived = 0");
            
        if($type === 'mobile')
            $banners_sql->andWhere('type <> "flash"');
            
        $banners = $banners_sql->groupBy('b.id')
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

            $segments = [];
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
            $view->save(array('date'=>time(), 'banner_id'=>$banner_selected->id));
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