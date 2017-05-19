<?php

namespace test;

use ArrayObject;
use arSql\Command;
use arSql\exception\NotSupportedException;
use test\MySqlHandler;
use arSql\Expression;
use arSql\Query;

class BuilderTest extends TestCase {

    protected $builder;

    public function setUp() {
        $this->builder = static::$command->getBuilder();
    }

    public function conditionProvider() {
        $conditions = array(
            // empty values
            array( array('like', 'name', array()), '0=1', array() ),
            array( array('not like', 'name', array()), '', array() ),
            array( array('or like', 'name', array()), '0=1', array() ),
            array( array('or not like', 'name', array()), '', array() ),

            // not
            array( array('not', 'name'), 'NOT (name)', array() ),

            // and
            array( array('and', 'id=1', 'id=2'), '(id=1) AND (id=2)', array() ),
            array( array('and', 'type=1', array('or', 'id=1', 'id=2')), '(type=1) AND ((id=1) OR (id=2))', array() ),
            array( array('and', 'id=1', new Expression('id=:qp0', array(':qp0' => 2))), '(id=1) AND (id=:qp0)', array(':qp0' => 2) ),

            // or
            array( array('or', 'id=1', 'id=2'), '(id=1) OR (id=2)', array() ),
            array( array('or', 'type=1', array('or', 'id=1', 'id=2')), '(type=1) OR ((id=1) OR (id=2))', array() ),
            array( array('or', 'type=1', new Expression('id=:qp0', array(':qp0' => 1))), '(type=1) OR (id=:qp0)', array(':qp0' => 1) ),

            // between
            array( array('between', 'id', 1, 10), '[[id]] BETWEEN :qp0 AND :qp1', array(':qp0' => 1, ':qp1' => 10) ),
            array( array('not between', 'id', 1, 10), '[[id]] NOT BETWEEN :qp0 AND :qp1', array(':qp0' => 1, ':qp1' => 10) ),
            array( array('between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), new Expression('NOW()')), '[[date]] BETWEEN (NOW() - INTERVAL 1 MONTH) AND NOW()', array() ),
            array( array('between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), 123), '[[date]] BETWEEN (NOW() - INTERVAL 1 MONTH) AND :qp0', array(':qp0' => 123) ),
            array( array('not between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), new Expression('NOW()')), '[[date]] NOT BETWEEN (NOW() - INTERVAL 1 MONTH) AND NOW()', array() ),
            array( array('not between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), 123), '[[date]] NOT BETWEEN (NOW() - INTERVAL 1 MONTH) AND :qp0', array(':qp0' => 123) ),

            // in
            array( array('in', 'id', array(1, 2, 3)), '[[id]] IN (:qp0, :qp1, :qp2)', array(':qp0' => 1, ':qp1' => 2, ':qp2' => 3) ),
            array( array('not in', 'id', array(1, 2, 3)), '[[id]] NOT IN (:qp0, :qp1, :qp2)', array(':qp0' => 1, ':qp1' => 2, ':qp2' => 3) ),
            array( array('in', 'id', Query::instantiate()->select('id')->from('users')->where(array('active' => 1))), '[[id]] IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', array(':qp0' => 1) ),
            array( array('not in', 'id', Query::instantiate()->select('id')->from('users')->where(array('active' => 1))), '[[id]] NOT IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', array(':qp0' => 1) ),
            array( array('in', 'id', 1),   '[[id]]=:qp0', array(':qp0' => 1) ),
            array( array('in', 'id', array(1)), '[[id]]=:qp0', array(':qp0' => 1) ),
            array( array('in', 'id', new ArrayObject(array(1))), '[[id]]=:qp0', array(':qp0' => 1) ),
            'composite in' => array(
                array('in', array('id', 'name'), array(array('id' =>1, 'name' => 'oy'))),
                '([[id]], [[name]]) IN ((:qp0, :qp1))',
                array(':qp0' => 1, ':qp1' => 'oy')
            ),

            // // in using array objects.
            array( array('id' => new ArrayObject(array(1, 2))), '[[id]] IN (:qp0, :qp1)', array(':qp0' => 1, ':qp1' => 2) ),
            array( array('in', 'id', new ArrayObject(array(1, 2, 3))), '[[id]] IN (:qp0, :qp1, :qp2)', array(':qp0' => 1, ':qp1' => 2, ':qp2' => 3) ),
            'composite in using array objects' => array(
                array('in', new ArrayObject(array('id', 'name')), new ArrayObject(array(
                    array('id' => 1, 'name' => 'oy'),
                    array('id' => 2, 'name' => 'yo'),
                ))),
                '([[id]], [[name]]) IN ((:qp0, :qp1), (:qp2, :qp3))',
                array(':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2, ':qp3' => 'yo'),
            ),

            // exists
            array( array('exists', Query::instantiate()->select('id')->from('users')->where(array('active' => 1))), 'EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', array(':qp0' => 1) ),
            array( array('not exists', Query::instantiate()->select('id')->from('users')->where(array('active' => 1))), 'NOT EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', array(':qp0' => 1) ),

            // simple conditions
            array( array('=', 'a', 'b'), '[[a]] = :qp0', array(':qp0' => 'b') ),
            array( array('>', 'a', 1), '[[a]] > :qp0', array(':qp0' => 1) ),
            array( array('>=', 'a', 'b'), '[[a]] >= :qp0', array(':qp0' => 'b') ),
            array( array('<', 'a', 2), '[[a]] < :qp0', array(':qp0' => 2) ),
            array( array('<=', 'a', 'b'), '[[a]] <= :qp0', array(':qp0' => 'b') ),
            array( array('<>', 'a', 3), '[[a]] <> :qp0', array(':qp0' => 3) ),
            array( array('!=', 'a', 'b'), '[[a]] != :qp0', array(':qp0' => 'b') ),
            array( array('>=', 'date', new Expression('DATE_SUB(NOW(), INTERVAL 1 MONTH)')), '[[date]] >= DATE_SUB(NOW(), INTERVAL 1 MONTH)', array() ),
            array( array('>=', 'date', new Expression('DATE_SUB(NOW(), INTERVAL :month MONTH)', array(':month' => 2))), '[[date]] >= DATE_SUB(NOW(), INTERVAL :month MONTH)', array(':month' => 2) ),
            // [ ['=', 'date', (new Query())->select('max(date)')->from('test')->where(['id' => 5])], '[[date]] = (SELECT max(date) FROM [[test]] WHERE [[id]]=:qp0)', [':qp0' => 5] ],

            // // hash condition
            array( array('a' => 1, 'b' => 2), '([[a]]=:qp0) AND ([[b]]=:qp1)', array(':qp0' => 1, ':qp1' => 2) ),
            array( array('a' => new Expression('CONCAT(col1, col2)'), 'b' => 2), '([[a]]=CONCAT(col1, col2)) AND ([[b]]=:qp0)', array(':qp0' => 2) ),

            // // direct conditions
            array( 'a = CONCAT(col1, col2)', 'a = CONCAT(col1, col2)', array() ),
            array( new Expression('a = CONCAT(col1, :param1)', array('param1' => 'value1')), 'a = CONCAT(col1, :param1)', array('param1' => 'value1') ),
        );

        // adjust dbms specific escaping
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = $this->replaceQuotes($condition[1]);
        }

        return $conditions;
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

    /**
     * @dataProvider conditionProvider
     */
    public function testBuildCondition($condition, $expected, $expectedParams) {
        $params = array();
        $sql = $this->builder->buildCondition($condition, $params);
        $this->assertEquals($expected, $sql);
        $this->assertEquals($expectedParams, $params);
    }

    public function testUpdate() {
        $sql = $this->builder->update('user', array('status' => 1), 'age > 30', $params);
        $this->assertEquals($sql, 'UPDATE `user` SET `status`=:qp0 WHERE age > 30');
        $this->assertEquals($params, array(':qp0' => 1));
    }

    public function testDelete() {
        $sql = $this->builder->delete('user', 'status = 0', $params);
        $this->assertEquals($sql, 'DELETE FROM `user` WHERE status = 0');
        $this->assertEmpty($params);
    }

}