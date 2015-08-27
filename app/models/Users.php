<?php
namespace App\Models;

use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\Validator\Email;
use Phalcon\Mvc\Model\Validator\StringLength;
use Phalcon\Mvc\Model\Validator\Uniqueness;
use Phalcon\Validation;

class Users extends ModelBase
{
    public $id, $fname, $lname, $thname, $email, $confirm, $password, $password_confirm, $type;


    public $labels = array(
        'fname'            => 'Имя',
        'lname'            => 'Фамилия',
        'thname'           => 'Отчество',
        'email'            => 'E-mail',
        'password'         => 'Пароль',
        'password_confirm' => 'Повтор пароля',
        'agreement'        => 'С условиями использования ресурса согласен, их принимаю',
    );

    public function initialize()
    {
        parent::initialize();
        $this->hasMany('id', __NAMESPACE__ . '\Tokens', 'user_id', array(
                'alias' => 'Tokens',
                'foreignKey' => array(
                    'action' => \Phalcon\Mvc\Model\Relation::ACTION_CASCADE
                )
            ));
    }

    public function validation()
    {
        $this->validate(new Email(array(
            'field' => 'email',
            'message' => 'Указан не действительный E-mail'
        )));
        $this->validate(new Uniqueness(array(
            "field" => "email",
            "message" => "Такой E-mail уже используется."
        )));
        $this->validate(new StringLength(array(
            "field" => "fname",
            "max" => 40,
            'messageMaximum' => 'Длина текста в поле "'.$this->getLabel('fname').'" должна быть не более 40 символов',
        )));
        $this->validate(new StringLength(array(
            "field" => "lname",
            "max" => 40,
            'messageMaximum' => 'Длина текста в поле "'.$this->getLabel('lname').'" должна быть не более 40 символов',
        )));
        $this->validate(new StringLength(array(
            "field" => "thname",
            "max" => 40,
            'messageMaximum' => 'Длина текста в поле "'.$this->getLabel('thname').'" должна быть не более 40 символов',
        )));

        return $this->validationHasFailed() != true;
    }

    public function afterValidationOnCreate() {
        $this->password = $this->getDI()->getAuth()->hash($this->password);
    }

    public function beforeUpdate() {
        $this->last_edit = time();
        $this->last_edit_ip = $this->getDI()->getRequest()->getClientAddress();
    }

    public function beforeCreate() {
        $this->created = time();
        $this->created_ip = $this->getDI()->getRequest()->getClientAddress();
    }

    public function getUsername() {
        return trim(implode(' ', array($this->lname, $this->fname, $this->thname)));
    }

    public function signup($data) {
        foreach($data as $k => $v) {
            $this->{$k} = $v;
        }
        $this->confirm = 1;

        $validation = new Validation();

        $validation->add('email', new Validation\Validator\Email(array(
            'message' => 'Указан не действительный E-mail'
        )));
        $validation->add('email', new \App\Library\Validators\Uniqueness(array(
            "model" =>'App\Models\Users',
            "message" => "Такой E-mail уже используется."
        )));
        $validation->add('fname', new Validation\Validator\PresenceOf(array(
            'message' => 'Поле "Имя" должно быть заполнено'
        )));
        $validation->add('fname', new Validation\Validator\StringLength(array(
            "max" => 40,
            'messageMaximum' => 'Длина текста в поле "'.$this->getLabel('fname').'" должна быть не более 40 символов',
        )));
        $validation->add('lname', new Validation\Validator\StringLength(array(
            "max" => 40,
            'messageMaximum' => 'Длина текста в поле "'.$this->getLabel('lname').'" должна быть не более 40 символов',
        )));
        $validation->add('thname', new Validation\Validator\StringLength(array(
            "max" => 40,
            'messageMaximum' => 'Длина текста в поле "'.$this->getLabel('thname').'" должна быть не более 40 символов',
        )));
        $validation->add('password', new Validation\Validator\PresenceOf(array(
            'message' => 'Пароль должен быть указан'
        )));
        $validation->add('password', new Validation\Validator\Confirmation(array(
            'with' => 'password_confirm',
            'message' => '"Пароль" и "Повтор пароля" должны совпадать'
        )));

        $messages = $validation->validate($data);

        if (count($messages)) {
            return $validation->getMessages();
        } else {
            if ($this->create() === true) {
                return true;
            } else {
                return $this->getMessages();
            }
        }
    }
}