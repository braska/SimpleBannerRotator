<?php

namespace App\Models;

class BannersZones extends ModelBase
{
    public function initialize()
    {
        parent::initialize();
        $this->belongsTo('banner_id', 'App\Models\Banners', 'id',
            array('alias' => 'banner')
        );
        $this->belongsTo('zone_id', 'App\Models\Zones', 'id',
            array('alias' => 'zone')
        );
    }
}
