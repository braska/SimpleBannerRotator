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
    
    public static function mobile_secret_key(){
        return base64_encode(sha1(\Phalcon\DI::getDefault()->get('config')->crypt->key));
    }
    
    function get_mobile_link(){
        return 'http://' . $_SERVER['HTTP_HOST'] . '/rotator/get?type=mobile&zone_id=' . $this->id . '&' . sha1($_SERVER['HTTP_HOST']) . '=' . $this->mobile_secret_key();
    }
}
