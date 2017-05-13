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
            'user_name' => 'JJ',
            'age' => 25,
        ), $params);

        $this->assertEquals($sql, "INSERT INTO `user` (`user_name`, `age`) VALUES (:qp0, :qp1)");
        $this->assertEquals($params, array(
            ":qp0" => "JJ",
            ":qp1" => 25,
        ));
    }

}