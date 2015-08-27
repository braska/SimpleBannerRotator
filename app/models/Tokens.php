<?php

namespace App\Models;

class Tokens extends ModelBase
{

    public function getSource()
    {
        return 'user_tokens';
    }

    public function initialize()
    {
        parent::initialize();
        $this->belongsTo('user_id', __NAMESPACE__ . '\Users', 'id', array(
            'alias' => 'User',
            'foreignKey' => true
        ));

        // Do garbage collection
        if (mt_rand(1, 100) === 1) {
            $this->delete_expired();
        }

        // This object has expired
        if (property_exists($this, 'expires') && $this->expires < time()) {
            $this->delete();
        }
    }

    public function delete_expired()
    {
        $this->getDI()->getShared('db')->execute('DELETE FROM `user_tokens` WHERE `expires` < :time', array(':time' => time()));
    }

}
