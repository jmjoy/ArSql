<?php

namespace test;

use arSql\Query;
use arSql\Expression;

class QueryTest extends TestCase {

    public function setUp() {
        
    }

    public function testSelect() {
        // default
        $query = new Query;
        $query->select('*');
        $this->assertEquals(array('*'), $query->select);
        $this->assertNull($query->distinct);
        $this->assertEquals(null, $query->selectOption);

        $query = new Query;
        $query->select('id, name', 'something')->distinct(true);
        $this->assertEquals(array('id', 'name'), $query->select);
        $this->assertTrue($query->distinct);
        $this->assertEquals('something', $query->selectOption);

        $query = new Query();
        $query->addSelect('email');
        $this->assertEquals(array('email'), $query->select);

        $query = new Query();
        $query->select('id, name');
        $query->addSelect('email');
        $this->assertEquals(array('id', 'name', 'email'), $query->select);
    }

    public function testFrom()
    {
        $query = new Query;
        $query->from('user');
        $this->assertEquals(array('user'), $query->from);
    }

    public function testWhere()
    {
        $query = new Query;
        $query->where('id = :id', array(':id' => 1));
        $this->assertEquals('id = :id', $query->where);
        $this->assertEquals(array(':id' => 1), $query->params);

        $query->andWhere('name = :name', array(':name' => 'something'));
        $this->assertEquals(array('and', 'id = :id', 'name = :name'), $query->where);
        $this->assertEquals(array(':id' => 1, ':name' => 'something'), $query->params);

        $query->orWhere('age = :age', array(':age' => '30'));
        $this->assertEquals(array('or', array('and', 'id = :id', 'name = :name'), 'age = :age'), $query->where);
        $this->assertEquals(array(':id' => 1, ':name' => 'something', ':age' => '30'), $query->params);
    }

    public function testFilterWhereWithHashFormat()
    {
        $query = new Query;
        $query->filterWhere(array(
            'id' => 0,
            'title' => '   ',
            'author_ids' => array(),
        ));
        $this->assertEquals(array('id' => 0), $query->where);

        $query->andFilterWhere(array('status' => null));
        $this->assertEquals(array('id' => 0), $query->where);

        $query->orFilterWhere(array('name' => ''));
        $this->assertEquals(array('id' => 0), $query->where);
    }

    public function testFilterWhereWithOperatorFormat()
    {
        $query = new Query;
        $condition = array('like', 'name', 'Alex');
        $query->filterWhere($condition);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(array('between', 'id', null, null));
        $this->assertEquals($condition, $query->where);

        $query->orFilterWhere(array('not between', 'id', null, null));
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(array('in', 'id', array()));
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(array('not in', 'id', array()));
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(array('like', 'id', ''));
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(array('or like', 'id', ''));
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(array('not like', 'id', '   '));
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(array('or not like', 'id', null));
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(array('or', array('eq', 'id', null), array('eq', 'id', array())));
        $this->assertEquals($condition, $query->where);
    }

    public function testFilterHavingWithHashFormat()
    {
        $query = new Query;
        $query->filterHaving(array(
            'id' => 0,
            'title' => '   ',
            'author_ids' => array(),
        ));
        $this->assertEquals(array('id' => 0), $query->having);

        $query->andFilterHaving(array('status' => null));
        $this->assertEquals(array('id' => 0), $query->having);

        $query->orFilterHaving(array('name' => ''));
        $this->assertEquals(array('id' => 0), $query->having);
    }

    public function testFilterHavingWithOperatorFormat()
    {
        $query = new Query;
        $condition = array('like', 'name', 'Alex');
        $query->filterHaving($condition);
        $this->assertEquals($condition, $query->having);

        $query->andFilterHaving(array('between', 'id', null, null));
        $this->assertEquals($condition, $query->having);

        $query->orFilterHaving(array('not between', 'id', null, null));
        $this->assertEquals($condition, $query->having);

        $query->andFilterHaving(array('in', 'id', array()));
        $this->assertEquals($condition, $query->having);

        $query->andFilterHaving(array('not in', 'id', array()));
        $this->assertEquals($condition, $query->having);

        $query->andFilterHaving(array('like', 'id', ''));
        $this->assertEquals($condition, $query->having);

        $query->andFilterHaving(array('or like', 'id', ''));
        $this->assertEquals($condition, $query->having);

        $query->andFilterHaving(array('not like', 'id', '   '));
        $this->assertEquals($condition, $query->having);

        $query->andFilterHaving(array('or not like', 'id', null));
        $this->assertEquals($condition, $query->having);

        $query->andFilterHaving(array('or', array('eq', 'id', null), array('eq', 'id', array())));
        $this->assertEquals($condition, $query->having);
    }

    public function testFilterRecursively()
    {
        $query = new Query();
        $query->filterWhere(array('and', array('like', 'name', ''), array('like', 'title', ''), array('id' => 1), array('not', array('like', 'name', ''))));
        $this->assertEquals(array('and', array('id' => 1)), $query->where);
    }

    public function testGroup()
    {
        $query = new Query;
        $query->groupBy('team');
        $this->assertEquals(array('team'), $query->groupBy);

        $query->addGroupBy('company');
        $this->assertEquals(array('team', 'company'), $query->groupBy);

        $query->addGroupBy('age');
        $this->assertEquals(array('team', 'company', 'age'), $query->groupBy);
    }

    public function testHaving()
    {
        $query = new Query;
        $query->having('id = :id', array(':id' => 1));
        $this->assertEquals('id = :id', $query->having);
        $this->assertEquals(array(':id' => 1), $query->params);

        $query->andHaving('name = :name', array(':name' => 'something'));
        $this->assertEquals(array('and', 'id = :id', 'name = :name'), $query->having);
        $this->assertEquals(array(':id' => 1, ':name' => 'something'), $query->params);

        $query->orHaving('age = :age', array(':age' => '30'));
        $this->assertEquals(array('or', array('and', 'id = :id', 'name = :name'), 'age = :age'), $query->having);
        $this->assertEquals(array(':id' => 1, ':name' => 'something', ':age' => '30'), $query->params);
    }

    public function testOrder()
    {
        $query = new Query;
        $query->orderBy('team');
        $this->assertEquals(array('team' => SORT_ASC), $query->orderBy);

        $query->addOrderBy('company');
        $this->assertEquals(array('team' => SORT_ASC, 'company' => SORT_ASC), $query->orderBy);

        $query->addOrderBy('age');
        $this->assertEquals(array('team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_ASC), $query->orderBy);

        $query->addOrderBy(array('age' => SORT_DESC));
        $this->assertEquals(array('team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_DESC), $query->orderBy);

        $query->addOrderBy('age ASC, company DESC');
        $this->assertEquals(array('team' => SORT_ASC, 'company' => SORT_DESC, 'age' => SORT_ASC), $query->orderBy);

        $expression = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');
        $query->orderBy($expression);
        $this->assertEquals(array($expression), $query->orderBy);

        $expression = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');
        $query->addOrderBy($expression);
        $this->assertEquals(array($expression, $expression), $query->orderBy);
    }

    public function testLimitOffset()
    {
        $query = new Query;
        $query->limit(10)->offset(5);
        $this->assertEquals(10, $query->limit);
        $this->assertEquals(5, $query->offset);
    }

    public function testLimitOffsetWithExpression()
    {
        $query = new Query();
        $query = $query->from('customer')->select('id')->orderBy('id');
        $query
            ->limit(new Expression('2'))
            ->offset(new Expression('1'));

        $result = $query->column();

        $this->assertCount(2, $result);

        $this->assertNotContains(1, $result);
        $this->assertContains(2, $result);
        $this->assertContains(3, $result);
    }

    public function testUnion()
    {
        $query = new Query;
        $query2 = new Query;
        $query->select(array('id', 'name'))
            ->from('item')
            ->limit(2)
            ->union(
                $query2
                ->select(array('id', 'name'))
                ->from(array('category'))
                ->limit(2)
            );
        $result = $query->all();
        $this->assertNotEmpty($result);
        $this->assertCount(4, $result);
    }

    public function testOne()
    {
        $query = new Query();
        $result = $query->from('customer')->where(array('status' => 2))->one();
        $this->assertEquals('user3', $result['name']);

        $query = new Query();
        $result = $query->from('customer')->where(array('status' => 3))->one();
        $this->assertFalse($result);
    }

    public function testExists()
    {
        $query = new Query();
        $result = $query->from('customer')->where(array('status' => 2))->exists();
        $this->assertTrue($result);

        $query = new Query();
        $result = $query->from('customer')->where(array('status' => 3))->exists();
        $this->assertFalse($result);
    }

    public function testColumn()
    {
        $query = new Query();
        $result = $query->select('name')->from('customer')->orderBy(array('id' => SORT_DESC))->column();
        $this->assertEquals(array('user3', 'user2', 'user1'), $result);

        // https://github.com/yiisoft/yii2/issues/7515
        $query = new Query();
        $result = $query->from('customer')
            ->select('name')
            ->orderBy(array('id' => SORT_DESC))
            ->indexBy('id')
            ->column();
        $this->assertEquals(array(3 => 'user3', 2 => 'user2', 1 => 'user1'), $result);

        // https://github.com/yiisoft/yii2/issues/12649
        $query = new Query();
        $result = $query->from('customer')
            ->select(array('name', 'id'))
            ->orderBy(array('id' => SORT_DESC))
            ->indexBy(function ($row) {
                return $row['id'] * 2;
            })
            ->column();
        $this->assertEquals(array(6 => 'user3', 4 => 'user2', 2 => 'user1'), $result);

        $query = new Query();
        $result = $query->from('customer')
            ->select(array('name'))
            ->indexBy('name')
            ->orderBy(array('id' => SORT_DESC))
            ->column();
        $this->assertEquals(array('user3' => 'user3', 'user2' => 'user2', 'user1' => 'user1'), $result);
    }

    public function testCount()
    {
        $query = new Query();
        $count = $query->from('customer')->count('*');
        $this->assertEquals(3, $count);

        $query = new Query();
        $count = $query->from('customer')->where(array('status' => 2))->count('*');
        $this->assertEquals(1, $count);

        $query = new Query();
        $count = $query->select('`status`, COUNT(`id`)')->from('customer')->groupBy('status')->count('*');
        $this->assertEquals(2, $count);

        // testing that orderBy() should be ignored here as it does not affect the count anyway.
        $query = new Query();
        $count = $query->from('customer')->orderBy('status')->count('*');
        $this->assertEquals(3, $count);
    }

    /**
     * @depends testFilterWhereWithHashFormat
     * @depends testFilterWhereWithOperatorFormat
     */
    public function testAndFilterCompare()
    {
        $query = new Query;

        $result = $query->andFilterCompare('name', null);
        $this->assertNull($query->where);

        $query->andFilterCompare('name', '');
        $this->assertNull($query->where);

        $query->andFilterCompare('name', 'John Doe');
        $condition = array('=', 'name', 'John Doe');
        $this->assertEquals($condition, $query->where);

        $condition = array('and', $condition, array('like', 'name', 'Doe'));
        $query->andFilterCompare('name', 'Doe', 'like');
        $this->assertEquals($condition, $query->where);

        $condition[] = array('>', 'rating', '9');
        $query->andFilterCompare('rating', '>9');
        $this->assertEquals($condition, $query->where);

        $condition[] = array('<=', 'value', '100');
        $query->andFilterCompare('value', '<=100');
        $this->assertEquals($condition, $query->where);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8068
     *
     * @depends testCount
     */
    public function testCountHavingWithoutGroupBy()
    {
        $schemaType = static::$sqlHandler->schemaType();
        if (!in_array($schemaType, array('mysql'))) {
            $this->markTestSkipped("{$schemaType} does not support having without group by.");
        }

        $query = new Query();
        $count = $query->from('customer')->having(array('status' => 2))->count('*');
        $this->assertEquals(1, $count);
    }

    public function testEmulateExecution()
    {
        $query = new Query();
        $this->assertGreaterThan(0, $query->from('customer')->count('*'));

        $query = new Query();
        $rows = $query
            ->from('customer')
            ->emulateExecution()
            ->all();
        $this->assertSame(array(), $rows);

        $query = new Query();
        $row = $query
            ->from('customer')
            ->emulateExecution()
            ->one();
        $this->assertFalse($row);

        $query = new Query();
        $exists = $query
            ->from('customer')
            ->emulateExecution()
            ->exists();
        $this->assertFalse($exists);

        $query = new Query();
        $count = $query
            ->from('customer')
            ->emulateExecution()
            ->count('*');
        $this->assertSame(0, $count);

        $query = new Query();
        $sum = $query
            ->from('customer')
            ->emulateExecution()
            ->sum('id');
        $this->assertSame(0, $sum);

        $query = new Query();
        $sum = $query
            ->from('customer')
            ->emulateExecution()
            ->average('id');
        $this->assertSame(0, $sum);

        $query = new Query();
        $max = $query
            ->from('customer')
            ->emulateExecution()
            ->max('id');
        $this->assertNull($max);

        $query = new Query();
        $min = $query
            ->from('customer')
            ->emulateExecution()
            ->min('id');
        $this->assertNull($min);

        $query = new Query();
        $scalar = $query
            ->select(array('id'))
            ->from('customer')
            ->emulateExecution()
            ->scalar();
        $this->assertNull($scalar);

        $query = new Query();
        $column = $query
            ->select(array('id'))
            ->from('customer')
            ->emulateExecution()
            ->column();
        $this->assertSame(array(), $column);
    }

    /**
     * @param Connection $db
     * @param string $tableName
     * @param string $columnName
     * @param array $condition
     * @param string $operator
     * @return int
     */
    protected function countLikeQuery($tableName, $columnName, array $condition, $operator = 'or')
    {
        $whereCondition = array($operator);
        foreach ($condition as $value) {
            $whereCondition[] = array('like', $columnName, $value);
        }
        $query = new Query();
        $result = $query
            ->from($tableName)
            ->where($whereCondition)
            ->count('*');
        if (is_numeric($result)) {
            $result = (int) $result;
        }
        return $result;
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13745
     */
    public function testMultipleLikeConditions()
    {
        $tableName = 'like_test';
        $columnName = 'col';

        static::$sqlHandler->execute("DROP TABLE IF EXISTS `{$tableName}`");
        static::$sqlHandler->execute("CREATE TABLE `{$tableName}` (`{$columnName}` VARCHAR(64))");
        $query = new Query();
        $query->createCommand()->batchInsert($tableName, array('col'), array(
            array('test0'),
            array('test\1'),
            array('test\2'),
            array('foo%'),
            array('%bar'),
            array('%baz%'),
        ))->execute();

        // Basic tests
        $this->assertSame(1, $this->countLikeQuery($tableName, $columnName, array('test0')));
        $this->assertSame(2, $this->countLikeQuery($tableName, $columnName, array('test\\')));
        $this->assertSame(0, $this->countLikeQuery($tableName, $columnName, array('test%')));
        $this->assertSame(3, $this->countLikeQuery($tableName, $columnName, array('%')));

        // Multiple condition tests
        $this->assertSame(2, $this->countLikeQuery($tableName, $columnName, array(
            'test0',
            'test\1',
        )));
        $this->assertSame(3, $this->countLikeQuery($tableName, $columnName, array(
            'test0',
            'test\1',
            'test\2',
        )));
        $this->assertSame(3, $this->countLikeQuery($tableName, $columnName, array(
            'foo',
            '%ba',
        )));
    }

}