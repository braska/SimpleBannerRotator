<?php

namespace App\Models;

use App\Library\Functions;
use Phalcon\Db\RawValue;

class Banners extends ModelBase
{
    public $id, $name, $width, $height, $link, $target_blank, $priority, $type, $content, $max_impressions, $start_date, $end_date, $url_mask, $advertiser_id, $active, $archived;
    public $labels = array(
        'name' => 'Имя',
        'width' => 'Ширина',
        'height' => 'Высота',
        'link' => 'Ссылка',
        'target_blank' => 'Открывать в новой вкладке',
        'priority' => 'Приоритет',
        'type' => 'Тип баннера',
        'content' => 'Содержимое баннера',
        'max_impressions' => 'Предел по показам',
        'start_date' => 'Дата начала показа',
        'end_date' => 'Дата конца показа',
        'url_mask' => 'URL-маска',
        'advertiser_id' => 'Рекламодатель',
        'active' => 'Статус',
        'archived' => 'Архивный'
    );

    public function initialize()
    {
        parent::initialize();
        $this->belongsTo('advertiser_id', 'App\Models\Users', 'id',
            array('alias' => 'advertiser')
        );
        $this->hasManyToMany(
            "id",
            'App\Models\BannersZones',
            "banner_id",
            "zone_id",
            'App\Models\Zones',
            "id",
            array('alias' => 'zones')
        );
        $this->hasMany('id', 'App\Models\Views', 'banner_id', array(
            'alias' => 'views',
            'foreignKey' => array(
                'action' => \Phalcon\Mvc\Model\Relation::ACTION_CASCADE
            )
        ));
    }

    public function beforeValidation()
    {
        $this->max_impressions = abs($this->max_impressions);

        if(empty($this->max_impressions) || !$this->max_impressions) {
            $this->max_impressions = null;
        }
        if (!isset($this->target_blank) || $this->target_blank === "") {
            $this->target_blank = new RawValue('default');
        }
        if (empty($this->priority) || !$this->priority) {
            $this->priority = new RawValue('default');
        }
        if (!isset($this->active) || $this->active === "") {
            $this->active = new RawValue('default');
        }
        if (!isset($this->archived) || $this->archived === "") {
            $this->archived = new RawValue('default');
        }
        if (!isset($this->advertiser_id) || $this->advertiser_id === "") {
            $this->advertiser_id = new RawValue('default');
        }
        if (!isset($this->width) || $this->width === "") {
            $this->width = '';
        }
        if (!isset($this->height) || $this->height === "") {
            $this->height = '';
        }
        if (!isset($this->start_date) || $this->start_date === "") {
            $this->start_date = new RawValue('default');
        }
        if (!isset($this->end_date) || $this->end_date === "") {
            $this->end_date = new RawValue('default');
        }
    }

    public function getSize() {
        $segment1 = '';
        $segment2 = '';
        $segment3 = '';
        if(!empty($this->width) || !empty($this->height)) {
            $segment2 = 'x';
        } else {
            return 'Не задан';
        }
        if(empty($this->width)) {
            $segment1 = '?';
        } else {
            $segment1 = $this->width;
        }

        if(empty($this->height)) {
            $segment3 = '?';
        } else {
            $segment3 = $this->height;
        }
        return $segment1.$segment2.$segment3;
    }

    public function getType() {
        if($this->type == "image") {
            return "Изображение";
        } elseif($this->type == "flash") {
            return "Flash";
        } elseif($this->type == "html") {
            return "HTML-код";
        } else {
            return false;
        }
    }

    public function getStartDate() {
        return Functions::formatted_unixtime($this->start_date);
    }

    public function getEndDate() {
        return Functions::formatted_unixtime($this->end_date);
    }

    public function toggle() {
        if($this->active == 1)
            $result = $this->update(array('active'=>0));
        else
            $result = $this->update(array('active'=>1));
        return $result;
    }

    public function toggleArchived() {
        if($this->archived == 1)
            $result = $this->update(array('archived'=>0));
        else
            $result = $this->update(array('archived'=>1));
        return $result;
    }
}
