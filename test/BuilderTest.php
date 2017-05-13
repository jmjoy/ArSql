<?php

namespace test;

use arSql\Command;
use arSql\exception\NotSupportedException;
use test\MySqlHandler;

class BuilderTest extends TestCase {

    protected $builder;

    protected function setUp() {
        $command = new Command(new MySqlHandler());
        $this->builder = $command->getBuilder();
    }

    public function testInsert() {
        $sql = $this->builder->insert('user', array(
            'name' => '\"\'J\'J\"',
            'age' => 25,
        ), $params);

        $this->assertEquals($sql, "INSERT INTO `user` (`name`, `age`) VALUES (:qp0, :qp1)");
        $this->assertEquals($params, array(
            ":qp0" => '\"\'J\'J\"',
            ":qp1" => 25,
        ));
    }

    public function testBatchInsert() {
        $sql = $this->builder->batchInsert('user', array('name', 'age'), array(
            array("JJ\'\"\n", 25),
            array('April', 26),
            array('May', 27),
        ));

        $this->assertEquals($sql, "INSERT INTO `user` (`name`, `age`) VALUES ('JJ\\\\''\"\\n', 25), ('April', 26), ('May', 27)");
    }

}