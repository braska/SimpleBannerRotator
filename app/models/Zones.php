<?php

namespace App\Models;

class Zones extends ModelBase
{
    public $id, $name;
    public $labels = array(
        'name' => 'Имя'
    );

    public function initialize()
    {
        parent::initialize();
        $this->hasManyToMany(
            "id",
            'App\Models\BannersZones',
            "zone_id",
            "banner_id",
            'App\Models\Banners',
            "id",
            array('alias' => 'banners')
        );
    }
}
