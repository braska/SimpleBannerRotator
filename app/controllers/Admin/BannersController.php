<?php

namespace App\Controllers\Admin;

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
        if($this->request->getQuery('zone')) {
            $zone = Zones::findFirst($this->request->getQuery('zone'));
        }
        if($this->request->getQuery('advertiser')) {
            $advertiser = Users::findFirst($this->request->getQuery('advertiser'));
        }

        $this->view->banners = $this->find();
        $this->view->title = Functions::mb_ucfirst(trim(($this->request->getQuery('archived') == '1' ? " архивные" : '').(($this->request->getQuery('filter') == 'deactivated') ? " деактивированные" : (($this->request->getQuery('filter') == 'finished') ? " выполненные" : ''))." баннеры")).(($this->request->getQuery('archive') == '1') ? " в архиве" : '').((isset($advertiser) && $advertiser) ? " рекламодателя \"{$advertiser->getUsername()}\"" : '').((isset($zone) && $zone) ? " в зоне \"{$zone->name}\"" : '');
        \Phalcon\Tag::prependTitle("Баннеры");
    }

    public function addAction() {
        $this->assets->collection('bottom-js')
            ->addJs('js/moment/moment.min.js')
            ->addJs('js/moment/ru.js')
            ->addJs('js/datetimepicker/js/bootstrap-datetimepicker.js');
        $this->assets->collection('css')
            ->addCss('js/datetimepicker/css/bootstrap-datetimepicker.min.css');
        $banner = new Banners();
        if ($this->request->isPost())
        {
            $data = $this->request->getPost();
            $data['target_blank'] = (bool)$this->request->get('target_blank') ? '1' : '0';
            if($this->request->getPost('start_date')) {
                $start_date = date_parse_from_format('d.m.Y H:i', $data['start_date']);
                $data['start_date'] = mktime($start_date['hour'], $start_date['minute'], 0, $start_date['month'], $start_date['day'], $start_date['year']);
            }
            if($this->request->getPost('end_date')) {
                $end_date = date_parse_from_format('d.m.Y H:i', $data['end_date']);
                $data['end_date'] = mktime($end_date['hour'], $end_date['minute'], 0, $end_date['month'], $end_date['day'], $end_date['year']);
            }
            if(($data['type'] == "image" || $data['type'] == "flash") && $data['source'] == "local") {
                $data['content'] = Banners::findFirst(array('id = :id:', 'bind' => array('id' => $data['content'])))->content;
            }
            $this->db->begin();

            if($banner->save($data, array('name', 'width', 'height', 'link', 'target_blank', 'priority', 'type', 'content', 'max_impressions', 'start_date', 'end_date', 'url_mask', 'advertiser_id')) == false)
            {
                $this->db->rollback();
                foreach($banner->getMessages() as $message) {
                    $this->flashSession->error($message->getMessage());
                }
            }
            else {
                if(($this->request->getPost('type') == "image" || $this->request->getPost('type') == "flash") && $this->request->getPost('source') == "file") {
                    if ($this->request->hasFiles(true)) {
                        $file = $this->request->getUploadedFiles()[0];
                        $validation = new \Phalcon\Validation();
                        $validation->add('file', new UploadValid());
                        if($this->request->getPost('type') == "image") {
                            $validation->add('file', new UploadType(array('allowed' => array('jpg', 'jpeg', 'png', 'gif'))));
                            $validation->add('file', new UploadImage());
                        } elseif($this->request->getPost('type') == "flash") {
                            $validation->add('file', new UploadType(array('allowed' => array('swf'))));
                        }
                        $messages = $validation->validate($_FILES);
                        if(count($messages)) {
                            $this->db->rollback();
                            foreach ($validation->getMessages() as $message) {
                                $this->flashSession->error($message->getMessage());
                            }
                        } else {
                            $extension = pathinfo($file->getName(), PATHINFO_EXTENSION);
                            $name = $banner->id.'.'.$extension;
                            $file->moveTo((($this->request->getPost('type') == "image") ? $this->config->banners->imagePath : $this->config->banners->flashPath).$name);
                            if($banner->save(array('content'=>$name)) == false) {
                                $this->db->rollback();
                                foreach($banner->getMessages() as $message) {
                                    $this->flashSession->error($message->getMessage());
                                }
                            } else {
                                $this->db->commit();
                            }
                        }
                    } else {
                        $this->db->rollback();
                            $this->flashSession->error("Необходимо указать файл");
                    }
                } else {
                    $this->db->commit();
                }
                if($this->request->getPost('zones')) {
                    foreach ($this->request->getPost('zones') as $zone) {
                        $zone = Zones::findFirst($zone);
                        if($zone) {
                            $m = new BannersZones();
                            $m->banner_id = $banner->id;
                            $m->zone_id = $zone->id;
                            $m->create();
                        }
                    }
                }

                return $this->response->redirect(array('for'=>'controller', 'controller'=>'banners'));

            }
        }
        $this->view->checked_zones = $this->request->getPost('zones') ? $this->request->getPost('zones') : [];
        $this->view->banner = $banner;
        $this->view->pick("banners/edit");
        $this->view->title = "Добавление баннера";
        \Phalcon\Tag::prependTitle("Добавление баннера");
    }

    public function editAction() {
        $this->assets->collection('bottom-js')
            ->addJs('js/moment/moment.min.js')
            ->addJs('js/moment/ru.js')
            ->addJs('js/datetimepicker/js/bootstrap-datetimepicker.js');
        $this->assets->collection('css')
            ->addCss('js/datetimepicker/css/bootstrap-datetimepicker.min.css');
        $id = $this->dispatcher->getParam('id');
        $banner = Banners::findFirst($id);
        if($id && $banner) {
            if ($this->request->isPost()) {
                $old_banner_type = $banner->type;
                $old_banner_content = $banner->content;
                $data = $this->request->getPost();
                $data['target_blank'] = (bool)$this->request->get('target_blank') ? '1' : '0';
                if ($this->request->getPost('start_date')) {
                    $start_date = date_parse_from_format('d.m.Y H:i', $data['start_date']);
                    $data['start_date'] = mktime($start_date['hour'], $start_date['minute'], 0, $start_date['month'], $start_date['day'], $start_date['year']);
                }
                if ($this->request->getPost('end_date')) {
                    $end_date = date_parse_from_format('d.m.Y H:i', $data['end_date']);
                    $data['end_date'] = mktime($end_date['hour'], $end_date['minute'], 0, $end_date['month'], $end_date['day'], $end_date['year']);
                }
                if (($data['type'] == "image" || $data['type'] == "flash") && $data['source'] == "local") {
                    $data['content'] = Banners::findFirst(array('id = :id:', 'bind' => array('id' => $data['content'])))->content;
                }
                $this->db->begin();

                if ($banner->save($data, array('name', 'width', 'height', 'link', 'target_blank', 'priority', 'type', 'content', 'max_impressions', 'start_date', 'end_date', 'url_mask', 'advertiser_id')) == false) {
                    $this->db->rollback();
                    foreach ($banner->getMessages() as $message) {
                        $this->flashSession->error($message->getMessage());
                    }
                } else {
                    if (($this->request->getPost('type') == "image" || $this->request->getPost('type') == "flash") && $this->request->getPost('source') == "file") {
                        if ($this->request->hasFiles(true)) {
                            $file = $this->request->getUploadedFiles()[0];
                            $validation = new \Phalcon\Validation();
                            $validation->add('file', new UploadValid());
                            if ($this->request->getPost('type') == "image") {
                                $validation->add('file', new UploadType(array('allowed' => array('jpg', 'jpeg', 'png', 'gif'))));
                                $validation->add('file', new UploadImage());
                            } elseif ($this->request->getPost('type') == "flash") {
                                $validation->add('file', new UploadType(array('allowed' => array('swf'))));
                            }
                            $messages = $validation->validate($_FILES);
                            if (count($messages)) {
                                $this->db->rollback();
                                foreach ($validation->getMessages() as $message) {
                                    $this->flashSession->error($message->getMessage());
                                }
                            } else {
                                $extension = pathinfo($file->getName(), PATHINFO_EXTENSION);
                                $name = $banner->id. '_' . time() . '.' . $extension;
                                $file->moveTo((($this->request->getPost('type') == "image") ? $this->config->banners->imagePath : $this->config->banners->flashPath) . $name);
                                if ($banner->save(array('content' => $name)) == false) {
                                    $this->db->rollback();
                                    foreach ($banner->getMessages() as $message) {
                                        $this->flashSession->error($message->getMessage());
                                    }
                                } else {
                                    if(($old_banner_type == "image" || $old_banner_type == "flash") && !empty($old_banner_content)) {
                                        unlink(($old_banner_type == "image" ? $this->config->banners->imagePath : $this->config->banners->flashPath).$old_banner_content);
                                    }
                                    $this->db->commit();
                                }
                            }
                        } else {
                            $this->db->rollback();
                            $this->flashSession->error("Необходимо указать файл");
                        }
                    } else {
                        $this->db->commit();
                    }
                    BannersZones::find(array("banner_id=:banner:", 'bind' => array('banner' => $banner->id)))->delete();
                    if($this->request->getPost('zones')) {
                        foreach ($this->request->getPost('zones') as $zone) {
                            $zone = Zones::findFirst($zone);
                            if($zone) {
                                $m = new BannersZones();
                                $m->banner_id = $banner->id;
                                $m->zone_id = $zone->id;
                                $m->create();
                            }
                        }
                    }

                    return $this->response->redirect(array('for' => 'controller', 'controller' => 'banners'));

                }
            }
            $this->view->checked_zones = $this->request->getPost('zones') ? $this->request->getPost('zones') : \array_column($banner->getZones(array('columns'=>array('id')))->toArray(), 'id');
            $this->view->banner = $banner;
            $this->view->pick("banners/edit");
            $this->view->title = "Редактирование баннера";
            \Phalcon\Tag::prependTitle("Редактирование баннера");
        } else $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
    }

    public function deleteAction() {
        $id = $this->dispatcher->getParam('id');
        $banner = Banners::findFirst($id);
        if($id && $banner) {
            $banner->delete();
            $this->flashSession->success("Баннер удалён безвозвратно");
            return $this->response->redirect(array('for'=>'controller', 'controller'=>'banners'));
        } else $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
    }

    public function statisticAction() {
        $id = $this->dispatcher->getParam('id');
        $banner = Banners::findFirst($id);
        if($id && $banner) {
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

            if(!$this->request->getQuery('end_date')) {
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

    public function toggleAction() {
        $id = $this->dispatcher->getParam('id');
        $banner = Banners::findFirst($id);
        if($id && $banner) {
            if($banner->toggle()) {
                $this->flashSession->success($banner->active == 1 ? "Баннер активирован" : "Баннер деактивирован");
            } else {
                foreach($banner->getMessages() as $message) {
                    $this->flashSession->error($message->getMessage());
                }
            }
            if(isset($_SERVER['HTTP_REFERER']))
                return $this->response->redirect($_SERVER['HTTP_REFERER'], true);
            else
                return $this->response->redirect(array('for'=>'controller', 'controller'=>'banners'));
        } else $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
    }

    public function toggle_archivedAction() {
        $id = $this->dispatcher->getParam('id');
        $banner = Banners::findFirst($id);
        if($id && $banner) {
            if($banner->toggleArchived()) {
                $this->flashSession->success($banner->archived == 1 ? "Баннер помещён в архив" : "Баннер изъят из архива");
            } else {
                foreach($banner->getMessages() as $message) {
                    $this->flashSession->error($message->getMessage());
                }
            }
            if(isset($_SERVER['HTTP_REFERER']))
                return $this->response->redirect($_SERVER['HTTP_REFERER'], true);
            else
                return $this->response->redirect(array('for'=>'controller', 'controller'=>'banners'));
        } else $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
    }

    public function countAction() {
        if($this->request->isAjax()) {
            echo count($this->find());
        } else {
            $this->dispatcher->forward(array("namespace"=>'App\Controllers', "controller" => "error", "action" => "notFound"));
        }
    }

    private function find() {
        $banners = $this->modelsManager->createBuilder()
            ->from(array('b'=>'App\Models\Banners'));

        if ($this->request->getQuery('filter') == 'deactivated')
            $banners = $banners->andWhere('active = 0');
        elseif ($this->request->getQuery('filter') == 'finished') {
            // см. $banners->filter(...);
        }
        elseif(!$this->request->getQuery('archived') || $this->request->getQuery('archived') != 1)
            // это фильтр для активных баннеров, но вместе с ними показываются и те что ещё не начали показываться (start_date > time())
            $banners = $banners
                ->andWhere('(end_date IS NULL OR end_date > ' . time() . ") AND active = 1");
        if($this->request->getQuery('zone')) {
            $banners = $banners
                ->innerJoin('App\Models\BannersZones', 'b.id = bz.banner_id AND bz.zone_id = ' . $this->request->getQuery('zone', 'int'), 'bz');
        }
        if($this->request->getQuery('advertiser')) {
            $banners = $banners->andWhere('advertiser_id = '.$this->request->getQuery('advertiser', 'int'));
        }
        if($this->request->getQuery('archived') == '1') {
            $banners = $banners->andWhere('archived = 1');
        } else {
            $banners = $banners->andWhere('archived = 0');
        }
        $banners = $banners
            ->orderBy('b.id DESC')
            ->getQuery()
            ->execute();
        if ($this->request->getQuery('filter') == 'finished' || !$this->request->getQuery('archived') || $this->request->getQuery('archived') != 1) {
            $banners = $banners->filter(function($banner) {
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
                if ($this->request->getQuery('filter') == 'finished') {
                    $ok = false;
                    if (($banner->end_date != null && time() > $banner->end_date) ) {
                        $ok = true;
                    }
                    if ($banner->max_impressions != null && $count_views >= $banner->max_impressions) {
                        $ok = true;
                    }
                } elseif (!$this->request->getQuery('archived') || $this->request->getQuery('archived') != 1) {
                    if ($banner->max_impressions != null && $count_views >= $banner->max_impressions) {
                        $ok = false;
                    }
                }
                if ($ok) {
                    return $banner;
                }
            });
        }
        return $banners;
    }
}