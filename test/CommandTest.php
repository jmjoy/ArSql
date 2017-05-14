<?php

namespace test;

use arSql\Command;

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
    }

}
