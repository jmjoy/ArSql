<?php

namespace test\data;

use arSql\Model;

/**
 * Singer
 */
class Singer extends Model
{
    public $firstName;
    public $lastName;
    public $test;

    public function rules()
    {
        return array(
            array(array('lastName'), 'default', 'value' => 'Lennon'),
            array(array('lastName'), 'required'),
            array(array('underscore_style'), 'number'),
            array(array('test'), 'required', 'when' => function($model) { return $model->firstName === 'cebe'; }),
        );
    }
}

