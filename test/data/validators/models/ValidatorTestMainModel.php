<?php

namespace test\data\validators\models;

use test\data\ar\ActiveRecord;

class ValidatorTestMainModel extends ActiveRecord
{
    public $testMainVal = 1;

    public static function tableName()
    {
        return 'validator_main';
    }

    public function getReferences()
    {
        return $this->hasMany(ValidatorTestRefModel::className(), array('ref' => 'id'));
    }
}

