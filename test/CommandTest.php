<?php

namespace test;

use arSql\Command;
use arSql\ArSql;

class CommandTest extends TestCase {

    public function testInsert() {
        $user = array(
            'name' => 'JJ',
            'email' => '918734043@qq.com',
            'address' => '',
        );
        $id = static::$command->insert('user', $user)->execute();
        $this->assertEquals($id, 1);

        $result = static::$sqlHandler->queryOne("select * from user where name = 'JJ' limit 1");
        $this->assertEquals($user['email'], $result['email']);
    }

    /**
     * @depends testInsert
     */
    public function testUpdate() {
        $rowCount = static::$command
                  ->update('user', array('address' => 'AAABBBCCC'), array('name' => 'JJ'))
                  ->execute();

        $this->assertEquals($rowCount, 1);

        $result = static::$sqlHandler->queryOne("select * from user where name = 'JJ' limit 1");
        $this->assertEquals($result['address'], 'AAABBBCCC');
    }

    /**
     * @depends testUpdate
     */
    public function testDelete() {
        $rowCount = static::$command
                  ->delete('user', array('name' => 'JJ'))
                  ->execute();

        $this->assertEquals($rowCount, 1);

        $result = static::$sqlHandler->queryAll("select * from user where name = 'JJ' limit 1");
        $this->assertEmpty($result);
    }

    public function testBatchInsert() {
        $rowCount = static::$command
                  ->batchInsert('user', array('name', 'email'), array(
                      array('Tom', '123@xxx.com'),
                      array('Jane', '123@xxx.com'),
                      array('Linda', '123@xxx.com'),
                  ))
                  ->execute();

        $this->assertEquals($rowCount, 3);

        $count = static::$sqlHandler->queryScalar("select count(*) from user where email = '123@xxx.com'");
        $this->assertEquals($count, 3);

        static::prepareDatabase();
    }

    public function testStaticMethod() {
        ArSql::insert('user', array(
            'name' => 'JJ',
            'email' => '918734043@qq.com',
            'address' => '',
        ))->execute();

        ArSql::batchInsert('user', array('name', 'email'), array(
            array('Tom', '123@xxx.com'),
            array('Jane', '123@xxx.com'),
            array('Linda', '123@xxx.com'),
        ))->execute();

        ArSql::update('user', array('name' => 'Hello'), 'name = :name', array('name' => 'JJ'))->execute();
        ArSql::delete('user', 'name = :name', array('name' => 'Linda'))->execute();

        $resultSet = ArSql::createCommand("select * from user")->queryAll();
        $expected = array(
            array(
                "id" => "1",
                "name" => "Hello",
                "email" => "918734043@qq.com",
                "address" => "",
                "status" => "0",
            ),
            array(
                "id" => "2",
                "name" => "Tom",
                "email" => "123@xxx.com",
                "address" => null,
                "status" => "0",
            ),
            array(
                "id" => "3",
                "name" => "Jane",
                "email" => "123@xxx.com",
                "address" => null,
                "status" => "0",
            ),
        );

        $this->assertEquals($expected, $resultSet);

        static::prepareDatabase();
    }

}
