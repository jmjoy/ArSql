<?php

namespace test;

use test\data\ar\ActiveRecord;
use test\data\ar\Customer;
use test\data\ar\Profile;
use arSql\ActiveQuery;
use arSql\ArSql;

/**
 * Class ActiveQueryTest the base class for testing ActiveQuery
 */
class ActiveQueryTest extends TestCase
{
    public function testConstructor()
    {
        $config = array(
            'on' => array('a' => 'b'),
            'joinWith' => array('dummy relation'),
        );
        $query = new ActiveQuery(Customer::className(), $config);
        $this->assertEquals($query->modelClass, Customer::className());
        $this->assertEquals($query->on, $config['on']);
        $this->assertEquals($query->joinWith, $config['joinWith']);
    }

    // public function testTriggerInitEvent()
    // {
    //     $where = '1==1';
    //     $callback = function (\yii\base\Event $event) use ($where) {
    //         $event->sender->where = $where;
    //     };
    //     Event::on(ActiveQuery::className(), ActiveQuery::EVENT_INIT, $callback);
    //     $result = new ActiveQuery(Customer::className());
    //     $this->assertEquals($where, $result->where);
    //     Event::off(ActiveQuery::className(), ActiveQuery::EVENT_INIT, $callback);
    // }

    /**
     * @todo: tests for internal logic of prepare()
     */
    public function testPrepare()
    {
        $query = new ActiveQuery(Customer::className());
        $builder = ArSql::getSchema()->createBuilder();
        $result = $query->prepare($builder);
        $this->assertInstanceOf('arSql\\Query', $result);
    }

    public function testPopulate_EmptyRows()
    {
        $query = new ActiveQuery(Customer::className());
        $rows = array();
        $result = $query->populate(array());
        $this->assertEquals($rows, $result);
    }

    /**
     * @todo: tests for internal logic of populate()
     */
    public function testPopulate_FilledRows()
    {
        $query = new ActiveQuery(Customer::className());
        $rows = $query->all();
        $result = $query->populate($rows);
        $this->assertEquals($rows, $result);
    }

    /**
     * @todo: tests for internal logic of one()
     */
    public function testOne()
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->one();
        $this->assertInstanceOf('test\data\ar\Customer', $result);
    }

    /**
     * @todo: test internal logic of createCommand()
     */
    public function testCreateCommand()
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->createCommand();
        $this->assertInstanceOf('arSql\\Command', $result);
    }

    // /**
    //  * @todo: tests for internal logic of queryScalar()
    //  */
    // public function testQueryScalar()
    // {
    //     $query = new ActiveQuery(Customer::className());
    //     $result = $query->queryScalar('name', null);
    //     $this->assertEquals('user1', $result);
    // }

    /**
     * @todo: tests for internal logic of joinWith()
     */
    public function testJoinWith()
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->joinWith('profile');
        $this->assertEquals(array(
            array(array('profile'), true, 'LEFT JOIN')
        ), $result->joinWith);
    }

    /**
     * @todo: tests for internal logic of innerJoinWith()
     */
    public function testInnerJoinWith()
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->innerJoinWith('profile');
        $this->assertEquals(array(
            array(array('profile'), true, 'INNER JOIN')
        ), $result->joinWith);
    }

    // /**
    //  * @todo: tests for the regex inside getQueryTableName
    //  */
    // public function testGetQueryTableName_from_not_set()
    // {
    //     $query = new ActiveQuery(Customer::className());
    //     $result = $this->invokeMethod($query,'getTableNameAndAlias');
    //     $this->assertEquals(array('customer','customer'), $result);
    // }

    // public function testGetQueryTableName_from_set()
    // {
    //     $options = array('from' => array('alias'=>'customer'));
    //     $query = new ActiveQuery(Customer::className(), $options);
    //     $result = $this->invokeMethod($query,'getTableNameAndAlias');
    //     $this->assertEquals(array('customer','alias'), $result);
    // }

    public function testOnCondition()
    {
        $query = new ActiveQuery(Customer::className());
        $on = array('active' => true);
        $params = array('a' => 'b');
        $result = $query->onCondition($on, $params);
        $this->assertEquals($on, $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testAndOnCondition_on_not_set()
    {
        $query = new ActiveQuery(Customer::className());
        $on = array('active' => true);
        $params = array('a' => 'b');
        $result = $query->andOnCondition($on, $params);
        $this->assertEquals($on, $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testAndOnCondition_on_set()
    {
        $onOld = array('active' => true);
        $query = new ActiveQuery(Customer::className());
        $query->on = $onOld;

        $on = array('active' => true);
        $params = array('a' => 'b');
        $result = $query->andOnCondition($on, $params);
        $this->assertEquals(array('and', $onOld, $on), $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testOrOnCondition_on_not_set()
    {
        $query = new ActiveQuery(Customer::className());
        $on = array('active' => true);
        $params = array('a' => 'b');
        $result = $query->orOnCondition($on, $params);
        $this->assertEquals($on, $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testOrOnCondition_on_set()
    {
        $onOld = array('active' => true);
        $query = new ActiveQuery(Customer::className());
        $query->on = $onOld;

        $on = array('active' => true);
        $params = array('a' => 'b');
        $result = $query->orOnCondition($on, $params);
        $this->assertEquals(array('or', $onOld, $on), $result->on);
        $this->assertEquals($params, $result->params);
    }

    /**
     * @todo: tests for internal logic of viaTable()
     */
    public function testViaTable()
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->viaTable(Profile::className(), array('id' => 'item_id'));
        $this->assertInstanceOf('arSql\\ActiveQuery', $result);
        $this->assertInstanceOf('arSql\\ActiveQuery', $result->via);
    }

    public function testAlias_not_set()
    {
        $query = new ActiveQuery(Customer::className());
        $result = $query->alias('alias');
        $this->assertInstanceOf('arSql\\ActiveQuery', $result);
        $this->assertEquals(array('alias' => 'customer'), $result->from);
    }

    public function testAlias_yet_set()
    {
        $aliasOld = array('old');
        $query = new ActiveQuery(Customer::className());
        $query->from = $aliasOld;
        $result = $query->alias('alias');
        $this->assertInstanceOf('arSql\\ActiveQuery', $result);
        $this->assertEquals(array('alias' => 'old'), $result->from);
    }

    public function testGetTableNames_notFilledFrom()
    {
        $query = new ActiveQuery(Profile::className());

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(array(
            '{{' . Profile::tableName() . '}}' => '{{' . Profile::tableName() . '}}',
        ), $tables);
    }

    public function testGetTableNames_isFromArray()
    {
        $query = new ActiveQuery(null);
        $query->from = array(
            '{{prf}}' => '{{profile}}',
            '{{usr}}' => '{{user}}',
            '{{a b}}' => '{{c d}}',
        );

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(array(
            '{{prf}}' => '{{profile}}',
            '{{usr}}' => '{{user}}',
            '{{a b}}' => '{{c d}}',
        ), $tables);
    }

    public function testGetTableNames_isFromString()
    {
        $query = new ActiveQuery(null);
        $query->from = 'profile AS \'prf\', user "usr", `order`, "customer", "a b" as "c d"';

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(array(
            '{{prf}}' => '{{profile}}',
            '{{usr}}' => '{{user}}',
            '{{order}}' => '{{order}}',
            '{{customer}}' => '{{customer}}',
            '{{c d}}' => '{{a b}}',
        ), $tables);
    }

    public function testGetTableNames_isFromObject_generateException()
    {
        $query = new ActiveQuery(null);
        $query->from = new \stdClass;

        $this->setExpectedException('\arSql\exception\InvalidConfigException');

        $query->getTablesUsedInFrom();
    }

    public function testGetTablesAlias_notFilledFrom()
    {
        $query = new ActiveQuery(Profile::className());

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(array(
            '{{' . Profile::tableName() . '}}' => '{{' . Profile::tableName() . '}}',
        ), $tables);
    }

    public function testGetTablesAlias_isFromArray()
    {
        $query = new ActiveQuery(null);
        $query->from = array(
            '{{prf}}' => '{{profile}}',
            '{{usr}}' => '{{user}}',
            '{{a b}}' => '{{c d}}',
        );

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(array(
            '{{prf}}' => '{{profile}}',
            '{{usr}}' => '{{user}}',
            '{{a b}}' => '{{c d}}',
        ), $tables);
    }

    public function testGetTablesAlias_isFromString()
    {
        $query = new ActiveQuery(null);
        $query->from = 'profile AS \'prf\', user "usr", service srv, order, [a b] [c d], {{something}} AS myalias';

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(array(
            '{{prf}}' => '{{profile}}',
            '{{usr}}' => '{{user}}',
            '{{srv}}' => '{{service}}',
            '{{order}}' => '{{order}}',
            '{{c d}}' => '{{a b}}',
            '{{myalias}}' => '{{something}}'
        ), $tables);
    }

    public function testGetTablesAlias_isFromObject_generateException()
    {
        $query = new ActiveQuery(null);
        $query->from = new \stdClass;

        $this->setExpectedException('\arSql\exception\InvalidConfigException');

        $query->getTablesUsedInFrom();
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/14150
     */
    public function testGetTableAliasFromPrefixedTableName()
    {
        $query = new ActiveQuery(null);
        $query->from = '{{%order_item}}';

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(array(
            '{{%order_item}}' => '{{%order_item}}',
        ), $tables);
    }
}

