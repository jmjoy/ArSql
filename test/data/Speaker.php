<?php

namespace test\data;

use arSql\Model;

/**
 * Speaker
 */
class Speaker extends Model {

    public $firstName;
    public $lastName;

    public $customLabel;
    public $underscore_style;

    protected $protectedProperty;
    private $_privateProperty;

    public static $formName = 'Speaker';

    public function formName()
    {
        return static::$formName;
    }

    public function attributeLabels()
    {
        return array(
            'customLabel' => 'This is the custom label',
        );
    }

    public function rules()
    {
        return array();
    }

    public function scenarios()
    {
        return array(
            'test' => array('firstName', 'lastName', '!underscore_style'),
        );
    }
}
