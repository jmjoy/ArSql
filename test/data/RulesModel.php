<?php
namespace test\data;

use arSql\Model;


/**
 * model to test different rules combinations in ModelTest
 */
class RulesModel extends Model
{
    public $account_id;
    public $user_id;
    public $email;
    public $name;

    public $scenarios;

    public function scenarios() {
        return array_merge(parent::scenarios(), $this->scenarios);
    }
}
