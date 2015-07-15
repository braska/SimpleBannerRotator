<?php

namespace App\Controllers\Advertiser;

use App\Library\Functions;
use App\Library\Validators\UploadImage;
use App\Library\Validators\UploadType;
use App\Library\Validators\UploadValid;
use App\Models\Banners;
use App\Models\BannersZones;
use App\Models\Users;
use App\Models\Views;
use App\Models\Zones;

class BannersController extends ControllerBase {
    public function indexAction() {
        $this->view->active_banners = $this->find();
        $this->view->finished_banners = $this->find("finished");
        $this->view->title = "Мои баннеры";
        \Phalcon\Tag::prependTitle("Мои баннеры");
    }

    public function statisticAction() {
        $id = $this->dispatcher->getParam('id');
        $banner = Banners::findFirst($id);
        if($id && $banner && $banner->advertiser_id == $this->auth->get_user()->id) {
            $this->assets->collection('bottom-js')
                ->addJs('js/moment/moment.min.js')
                ->addJs('js/moment/ru.js')
                ->addJs('js/datetimepicker/js/bootstrap-datetimepicker.js');
            $this->assets->collection('css')
                ->addCss('js/datetimepicker/css/bootstrap-datetimepicker.min.css');
            if(!$this->request->getQuery('start_date')) {
                if (!empty($banner->start_date)) {
                    $start_date = $banner->start_date;
                } else {
                    $first_view = $banner->views->getFirst()->date;
                    if (!empty($first_view))
                        $start_date = $first_view;
                    else
                        $start_date = 0;
                }
            } else {
                $start_date = date_parse_from_format('d.m.Y H:i', $this->request->getQuery('start_date'));
                $start_date = mktime($start_date['hour'], $start_date['minute'], 0, $start_date['month'], $start_date['day'], $start_date['year']);
            }

            if(!$this->request->getQuery('start_date')) {
                if (!empty($banner->end_date)) {
                    if ($banner->end_date > time()) {
                        $end_date = time();
                    } else $end_date = $banner->end_date;
                } else
                    $end_date = time();
            } else {
                $end_date = date_parse_from_format('d.m.Y H:i', $this->request->getQuery('end_date'));
                $end_date = mktime($end_date['hour'], $end_date['minute'], 0, $end_date['month'], $end_date['day'], $end_date['year']);
            }
            $days = (floor(($end_date + 10800)  / 86400)) - (floor(($start_date + 10800) / 86400)) + 1;
            $days_arr = [];
            if($days > 0) {
                for($i = 0; $i < $days; $i++) {
                    $day = floor(($start_date + 10800) / 86400) * 86400 + $i*86400 - 10800;
                    if((!empty($banner->start_date) ? ($day >= (floor(($banner->start_date + 10800) / 86400) * 86400) - 10800) : true) && $day < (!empty($banner->end_date) ? $banner->end_date : (time())))
                        $days_arr[] = array('date' => $day, 'views'=>$banner->countViews("date >= {$day} AND date < ".($day + 86400)), 'clicks' => $banner->countViews("date >= {$day} AND date < ".($day + 86400)." AND clicked = 1"));
                }
            }

            $this->view->days = $days_arr;
            $q = "date >= {$start_date} AND date <= {$end_date}";
            $this->view->views = $banner->countViews(array($q));
            $q .= " AND clicked = 1";
            $this->view->clicks = $banner->countViews(array($q));
            $this->view->start_date = $start_date;
            $this->view->end_date = $end_date;
            $this->view->banner = $banner;
            $this->view->title = "Статистика для баннера \"{$banner->name}\"";
            \Phalcon\Tag::prependTitle("Статистика для баннера \"{$banner->name}\"");
        } else $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
    }

    private function find($type = "active") {
        $banners = $this->modelsManager->createBuilder()
            ->from(array('b'=>'App\Models\Banners'))
            ->where('advertiser_id = '.$this->auth->get_user()->id);

        if ($type == 'finished') {
            // см. $banners->filter(...);
        }
        elseif($type == "active")
            // это фильтр для активных баннеров, но вместе с ними показываются и те что ещё не начали показываться (start_date > time())
            $banners = $banners
                ->andWhere('(end_date IS NULL OR end_date > ' . time() . ") AND active = 1 AND archived = 0");

        $banners = $banners
            ->orderBy('b.id DESC')
            ->getQuery()
            ->execute();

        $banners = $banners->filter(function($banner) use ($type) {
            $ok = true;
            if ($banner->max_impressions != null) {
                if (($banner->start_date != null || $banner->end_date != null)) {
                    $count_conditions = [];
                    if ($banner->start_date != null) {
                        $count_conditions[] = 'date >= ' . $banner->start_date;
                    }
                    if ($banner->end_date != null) {
                        $count_conditions[] = 'date < ' . $banner->end_date;
                    }
                    $count_views = $banner->countViews([implode(' AND ', $count_conditions)]);
                } else {
                    $count_views = $banner->countViews();
                }
            }
            if ($type == 'finished') {
                $ok = false;
                if (($banner->end_date != null && time() > $banner->end_date) ) {
                    $ok = true;
                }
                if ($banner->max_impressions != null && $count_views >= $banner->max_impressions) {
                    $ok = true;
                }
            } elseif ($type =='active') {
                if ($banner->max_impressions != null && $count_views >= $banner->max_impressions) {
                    $ok = false;
                }
            }
            if ($ok) {
                return $banner;
            }
        });
        return $banners;
    }
}