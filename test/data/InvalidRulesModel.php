<?php

namespace test\data;

use arSql\Model;

/**
 * InvalidRulesModel
 */
class InvalidRulesModel extends Model
{
    public function rules()
    {
        return array(
            array('test'),
        );
    }
}

