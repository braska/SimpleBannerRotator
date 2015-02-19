<?php

namespace App\Models;

class Views extends ModelBase
{
    public function initialize()
    {
        parent::initialize();
        $this->belongsTo('banner_id', 'App\Models\Banners', 'id',
            array('alias' => 'banner')
        );
    }
}
