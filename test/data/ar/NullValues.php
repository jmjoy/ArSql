<?php

namespace test\data\ar;

/**
 * Class NullValues
 *
 * @property int $id
 * @property int $var1
 * @property int $var2
 * @property int $var3
 * @property string $stringcol
 */
class NullValues extends ActiveRecord
{
    public static function tableName()
    {
        return 'null_values';
    }
}


