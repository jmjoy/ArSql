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

    public function scenarios() {
        return array(
            static::SCENARIO_DEFAULT => array('lastName', 'underscore_style', 'test'),
        );
    }
}

