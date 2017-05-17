<?php
/**
 * @author Carsten Brandt <mail@cebe.cc>
 */

namespace test\data\ar;

/**
 * Class Profile
 *
 * @property int $id
 * @property string $description
 *
 */
class Profile extends ActiveRecord
{
    public static function tableName()
    {
        return 'profile';
    }
}


