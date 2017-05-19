<?php

namespace test;

use test\data\ar\Customer;
use test\data\ar\Order;
use test\data\ar\Item;
use test\data\ar\Category;
use test\data\ar\NullValues;
use test\data\ar\OrderItem;
use test\data\ar\Profile;

class ActiveRecordTest extends TestCase
{

    /**
     * @inheritdoc
     */
    public function getCustomerClass()
    {
        return Customer::className();
    }

    /**
     * @inheritdoc
     */
    public function getItemClass()
    {
        return Item::className();
    }

    /**
     * @inheritdoc
     */
    public function getOrderClass()
    {
        return Order::className();
    }

    /**
     * @inheritdoc
     */
    public function getOrderItemClass()
    {
        return OrderItem::className();
    }

    /**
     * @return string
     */
    public function getCategoryClass()
    {
        return Category::className();
    }

    /**
     * @inheritdoc
     */
    public function getOrderWithNullFKClass()
    {
        return OrderWithNullFK::className();
    }

    /**
     * @inheritdoc
     */
    public function getOrderItemWithNullFKmClass()
    {
        return OrderItemWithNullFK::className();
    }

    public function testCustomColumns()
    {
        $customer = Customer::find()->select(array('*', '(status*2) AS status2'))
                  ->where(array('name' => 'user3'))->one();
        $this->assertEquals(3, $customer->id);
        $this->assertEquals(4, $customer->status2);
    }

    public function testStatisticalFind()
    {
        // find count, sum, average, min, max, scalar
        $this->assertEquals(3, Customer::find()->count());
        $this->assertEquals(2, Customer::find()->where('id=1 OR id=2')->count());
        $this->assertEquals(6, Customer::find()->sum('id'));
        $this->assertEquals(2, Customer::find()->average('id'));
        $this->assertEquals(1, Customer::find()->min('id'));
        $this->assertEquals(3, Customer::find()->max('id'));
        $this->assertEquals(3, Customer::find()->select('COUNT(*)')->scalar());
    }

   public function testFindScalar()
   {
       // query scalar
       $customerName = Customer::find()->where(array('id' => 2))->select('name')->scalar();
       $this->assertEquals('user2', $customerName);
   }

    public function testFindExists()
    {
        $this->assertTrue(Customer::find()->where(array('id' => 2))->exists());
        $this->assertFalse(Customer::find()->where(array('id' => 42))->exists());
        $this->assertTrue(Customer::find()->where(array('id' => 2))->select('name')->exists());
        $this->assertFalse(Customer::find()->where(array('id' => 42))->select('name')->exists());
    }

   public function testFindColumn()
   {
       /* @var $this TestCase|ActiveRecordTestTrait */
       $this->assertEquals(array('user1', 'user2', 'user3'), Customer::find()->select('name')->column());
       $this->assertEquals(array('user3', 'user2', 'user1'), Customer::find()->orderBy(array('name' => SORT_DESC))->select('name')->column());
   }

    public function testFindBySql()
    {
        // find one
        $customer = Customer::findBySql('SELECT * FROM customer ORDER BY id DESC')->one();
        $this->assertInstanceOf(Customer::className(), $customer);
        $this->assertEquals('user3', $customer->name);

        // find all
        $customers = Customer::findBySql('SELECT * FROM customer')->all();
        $this->assertCount(3, $customers);

        // find with parameter binding
        $customer = Customer::findBySql('SELECT * FROM customer WHERE id=:id', array(':id' => 2))->one();
        $this->assertInstanceOf(Customer::className(), $customer);
        $this->assertEquals('user2', $customer->name);
    }

    /**
     * @depends testFindBySql
     *
     * @see https://github.com/yiisoft/yii2/issues/8593
     */
    public function testCountWithFindBySql()
    {
        $query = Customer::findBySql('SELECT * FROM customer');
        $this->assertEquals(3, $query->count());
        $query = Customer::findBySql('SELECT * FROM customer WHERE  id=:id', array(':id' => 2));
        $this->assertEquals(1, $query->count());
    }

    public function testFindLazyViaTable()
    {
        /* @var $order Order */
        $order = Order::findOne(1);
        $this->assertEquals(1, $order->id);
        $this->assertCount(2, $order->books);
        $this->assertEquals(1, $order->items[0]->id);
        $this->assertEquals(2, $order->items[1]->id);

        $order = Order::findOne(2);
        $this->assertEquals(2, $order->id);
        $this->assertCount(0, $order->books);

        $order = Order::find()->where(array('id' => 1))->asArray()->one();
        $this->assertTrue(is_array($order));
    }

   public function testFindEagerViaTable()
   {
       $orders = Order::find()->with('books')->orderBy('id')->all();
       $this->assertCount(3, $orders);

       $order = $orders[0];
       $this->assertEquals(1, $order->id);
       $this->assertCount(2, $order->books);
       $this->assertEquals(1, $order->books[0]->id);
       $this->assertEquals(2, $order->books[1]->id);

       $order = $orders[1];
       $this->assertEquals(2, $order->id);
       $this->assertCount(0, $order->books);

       $order = $orders[2];
       $this->assertEquals(3, $order->id);
       $this->assertCount(1, $order->books);
       $this->assertEquals(2, $order->books[0]->id);

       // https://github.com/yiisoft/yii2/issues/1402
       $orders = Order::find()->with('books')->orderBy('id')->asArray()->all();
       $this->assertCount(3, $orders);
       $this->assertTrue(is_array($orders[0]['orderItems'][0]));

       $order = $orders[0];
       $this->assertTrue(is_array($order));
       $this->assertEquals(1, $order['id']);
       $this->assertCount(2, $order['books']);
       $this->assertEquals(1, $order['books'][0]['id']);
       $this->assertEquals(2, $order['books'][1]['id']);
   }

    // deeply nested table relation
    public function testDeeplyNestedTableRelation()
    {
        /* @var $customer Customer */
        $customer = Customer::findOne(1);
        $this->assertNotNull($customer);

        $items = $customer->orderItems;

        $this->assertCount(2, $items);
        $this->assertInstanceOf(Item::className(), $items[0]);
        $this->assertInstanceOf(Item::className(), $items[1]);
        $this->assertEquals(1, $items[0]->id);
        $this->assertEquals(2, $items[1]->id);
    }

    /**
     * https://github.com/yiisoft/yii2/issues/5341
     *
     * Issue:     Plan     1 -- * Account * -- * User
     * Our Tests: Category 1 -- * Item    * -- * Order
     */
    public function testDeeplyNestedTableRelation2()
    {
        /* @var $category Category */
        $category = Category::findOne(1);
        $this->assertNotNull($category);
        $orders = $category->orders;
        $this->assertCount(2, $orders);
        $this->assertInstanceOf(Order::className(), $orders[0]);
        $this->assertInstanceOf(Order::className(), $orders[1]);
        $ids = array($orders[0]->id, $orders[1]->id);
        sort($ids);
        $this->assertEquals(array(1, 3), $ids);

        $category = Category::findOne(2);
        $this->assertNotNull($category);
        $orders = $category->orders;
        $this->assertCount(1, $orders);
        $this->assertInstanceOf(Order::className(), $orders[0]);
        $this->assertEquals(2, $orders[0]->id);

    }

    public function testStoreNull()
    {
        $record = new NullValues();
        $this->assertNull($record->var1);
        $this->assertNull($record->var2);
        $this->assertNull($record->var3);
        $this->assertNull($record->stringcol);

        $record->var1 = 123;
        $record->var2 = 456;
        $record->var3 = 789;
        $record->stringcol = 'hello!';

        $record->save();
        $this->assertTrue($record->refresh());

        $this->assertEquals(123, $record->var1);
        $this->assertEquals(456, $record->var2);
        $this->assertEquals(789, $record->var3);
        $this->assertEquals('hello!', $record->stringcol);

        $record->var1 = null;
        $record->var2 = null;
        $record->var3 = null;
        $record->stringcol = null;

        $record->save();
        $this->assertTrue($record->refresh());

        $this->assertNull($record->var1);
        $this->assertNull($record->var2);
        $this->assertNull($record->var3);
        $this->assertNull($record->stringcol);

        $record->var1 = 0;
        $record->var2 = 0;
        $record->var3 = 0;
        $record->stringcol = '';

        $record->save();
        $this->assertTrue($record->refresh());

        $this->assertEquals(0, $record->var1);
        $this->assertEquals(0, $record->var2);
        $this->assertEquals(0, $record->var3);
        $this->assertEquals('', $record->stringcol);
    }

    public function testStoreEmpty()
    {
        $record = new NullValues();

        // this is to simulate empty html form submission
        $record->var1 = 0;
        $record->var2 = 0;
        $record->var3 = 0;
        $record->stringcol = '';

        $record->save();
        $this->assertTrue($record->refresh());

        // https://github.com/yiisoft/yii2/commit/34945b0b69011bc7cab684c7f7095d837892a0d4#commitcomment-4458225
        $this->assertSame($record->var1, $record->var2);
        $this->assertSame($record->var2, $record->var3);
    }

    public function testIsPrimaryKey()
    {
        $this->assertFalse(Customer::isPrimaryKey(array()));
        $this->assertTrue(Customer::isPrimaryKey(array('id')));
        $this->assertFalse(Customer::isPrimaryKey(array('id', 'name')));
        $this->assertFalse(Customer::isPrimaryKey(array('name')));
        $this->assertFalse(Customer::isPrimaryKey(array('name', 'email')));

        $this->assertFalse(OrderItem::isPrimaryKey(array()));
        $this->assertFalse(OrderItem::isPrimaryKey(array('order_id')));
        $this->assertFalse(OrderItem::isPrimaryKey(array('item_id')));
        $this->assertFalse(OrderItem::isPrimaryKey(array('quantity')));
        $this->assertFalse(OrderItem::isPrimaryKey(array('quantity', 'subtotal')));
        $this->assertTrue(OrderItem::isPrimaryKey(array('order_id', 'item_id')));
        $this->assertFalse(OrderItem::isPrimaryKey(array('order_id', 'item_id', 'quantity')));
    }

    public function testJoinWith()
    {
        // left join and eager loading
        $orders = Order::find()->joinWith('customer')->orderBy('customer.id DESC, order.id')->all();
        $this->assertCount(3, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertEquals(1, $orders[2]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('customer'));
        $this->assertTrue($orders[1]->isRelationPopulated('customer'));
        $this->assertTrue($orders[2]->isRelationPopulated('customer'));

        // inner join filtering and eager loading
        $orders = Order::find()->innerJoinWith(array(
            'customer' => function ($query) {
                $query->where('customer.id=2');
            },
        ))->orderBy('order.id')->all();
        $this->assertCount(2, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('customer'));
        $this->assertTrue($orders[1]->isRelationPopulated('customer'));

        // inner join filtering, eager loading, conditions on both primary and relation
        $orders = Order::find()->innerJoinWith(array(
            'customer' => function ($query) {
                $query->where(array('customer.id' => 2));
            },
        ))->where(array('order.id' => array(1, 2)))->orderBy('order.id')->all();
        $this->assertCount(1, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('customer'));

        // inner join filtering without eager loading
        $orders = Order::find()->innerJoinWith(array(
            'customer' => function ($query) {
                $query->where('customer.id=2');
            },
        ), false)->orderBy('order.id')->all();
        $this->assertCount(2, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertFalse($orders[0]->isRelationPopulated('customer'));
        $this->assertFalse($orders[1]->isRelationPopulated('customer'));

        // inner join filtering without eager loading, conditions on both primary and relation
        $orders = Order::find()->innerJoinWith(array(
            'customer' => function ($query) {
                    $query->where(array('customer.id' => 2));
            },
        ), false)->where(array('order.id' => array(1, 2)))->orderBy('order.id')->all();
        $this->assertCount(1, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertFalse($orders[0]->isRelationPopulated('customer'));

        // join with via-relation
        $orders = Order::find()->innerJoinWith('books')->orderBy('order.id')->all();
        $this->assertCount(2, $orders);
        $this->assertEquals(1, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('books'));
        $this->assertTrue($orders[1]->isRelationPopulated('books'));
        $this->assertCount(2, $orders[0]->books);
        $this->assertCount(1, $orders[1]->books);

        // join with sub-relation
        $orders = Order::find()->innerJoinWith(array(
            'items' => function ($q) {
                $q->orderBy('item.id');
            },
            'items.category' => function ($q) {
                $q->where('category.id = 2');
            },
        ))->orderBy('order.id')->all();
        $this->assertCount(1, $orders);
        $this->assertTrue($orders[0]->isRelationPopulated('items'));
        $this->assertEquals(2, $orders[0]->id);
        $this->assertCount(3, $orders[0]->items);
        $this->assertTrue($orders[0]->items[0]->isRelationPopulated('category'));
        $this->assertEquals(2, $orders[0]->items[0]->category->id);

        // join with table alias
        $orders = Order::find()->joinWith(array(
            'customer' => function ($q) {
                $q->from('customer c');
            }
        ))->orderBy('c.id DESC, order.id')->all();
        $this->assertCount(3, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertEquals(1, $orders[2]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('customer'));
        $this->assertTrue($orders[1]->isRelationPopulated('customer'));
        $this->assertTrue($orders[2]->isRelationPopulated('customer'));

        // join with table alias
        $orders = Order::find()->joinWith('customer as c')->orderBy('c.id DESC, order.id')->all();
        $this->assertCount(3, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertEquals(1, $orders[2]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('customer'));
        $this->assertTrue($orders[1]->isRelationPopulated('customer'));
        $this->assertTrue($orders[2]->isRelationPopulated('customer'));

        // join with table alias sub-relation
        $orders = Order::find()->innerJoinWith(array(
            'items as t' => function ($q) {
                $q->orderBy('t.id');
            },
            'items.category as c' => function ($q) {
                $q->where('c.id = 2');
            },
        ))->orderBy('order.id')->all();
        $this->assertCount(1, $orders);
        $this->assertTrue($orders[0]->isRelationPopulated('items'));
        $this->assertEquals(2, $orders[0]->id);
        $this->assertCount(3, $orders[0]->items);
        $this->assertTrue($orders[0]->items[0]->isRelationPopulated('category'));
        $this->assertEquals(2, $orders[0]->items[0]->category->id);

        // join with ON condition
        $orders = Order::find()->joinWith('books2')->orderBy('order.id')->all();
        $this->assertCount(3, $orders);
        $this->assertEquals(1, $orders[0]->id);
        $this->assertEquals(2, $orders[1]->id);
        $this->assertEquals(3, $orders[2]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('books2'));
        $this->assertTrue($orders[1]->isRelationPopulated('books2'));
        $this->assertTrue($orders[2]->isRelationPopulated('books2'));
        $this->assertCount(2, $orders[0]->books2);
        $this->assertCount(0, $orders[1]->books2);
        $this->assertCount(1, $orders[2]->books2);

        // lazy loading with ON condition
        $order = Order::findOne(1);
        $this->assertCount(2, $order->books2);
        $order = Order::findOne(2);
        $this->assertCount(0, $order->books2);
        $order = Order::findOne(3);
        $this->assertCount(1, $order->books2);

        // eager loading with ON condition
        $orders = Order::find()->with('books2')->all();
        $this->assertCount(3, $orders);
        $this->assertEquals(1, $orders[0]->id);
        $this->assertEquals(2, $orders[1]->id);
        $this->assertEquals(3, $orders[2]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('books2'));
        $this->assertTrue($orders[1]->isRelationPopulated('books2'));
        $this->assertTrue($orders[2]->isRelationPopulated('books2'));
        $this->assertCount(2, $orders[0]->books2);
        $this->assertCount(0, $orders[1]->books2);
        $this->assertCount(1, $orders[2]->books2);

        // join with count and query
        $query = Order::find()->joinWith('customer');
        $count = $query->count();
        $this->assertEquals(3, $count);
        $orders = $query->all();
        $this->assertCount(3, $orders);

        // https://github.com/yiisoft/yii2/issues/2880
        $query = Order::findOne(1);
        $customer = $query->getCustomer()->joinWith(array(
            'orders' => function ($q) { $q->orderBy(array()); }
        ))->one();
        $this->assertEquals(1, $customer->id);
        $order = Order::find()->joinWith(array(
            'items' => function ($q) {
                $q->from(array('items' => 'item'))
                    ->orderBy('items.id');
            },
        ))->orderBy('order.id')->one();

        // join with sub-relation called inside Closure
        $orders = Order::find()->joinWith(array(
                'items' => function ($q) {
                    $q->orderBy('item.id');
                    $q->joinWith(array(
                            'category'=> function ($q) {
                                $q->where('category.id = 2');
                            }
                        ));
                },
            ))->orderBy('order.id')->all();
        $this->assertCount(1, $orders);
        $this->assertTrue($orders[0]->isRelationPopulated('items'));
        $this->assertEquals(2, $orders[0]->id);
        $this->assertCount(3, $orders[0]->items);
        $this->assertTrue($orders[0]->items[0]->isRelationPopulated('category'));
        $this->assertEquals(2, $orders[0]->items[0]->category->id);
    }

    public function testJoinWithAndScope()
    {
        // hasOne inner join
        $customers = Customer::find()->active()->innerJoinWith('profile')->orderBy('customer.id')->all();
        $this->assertCount(1, $customers);
        $this->assertEquals(1, $customers[0]->id);
        $this->assertTrue($customers[0]->isRelationPopulated('profile'));

        // hasOne outer join
        $customers = Customer::find()->active()->joinWith('profile')->orderBy('customer.id')->all();
        $this->assertCount(2, $customers);
        $this->assertEquals(1, $customers[0]->id);
        $this->assertEquals(2, $customers[1]->id);
        $this->assertTrue($customers[0]->isRelationPopulated('profile'));
        $this->assertTrue($customers[1]->isRelationPopulated('profile'));
        $this->assertInstanceOf(Profile::className(), $customers[0]->profile);
        $this->assertNull($customers[1]->profile);

        // hasMany
        $customers = Customer::find()->active()->joinWith(array(
            'orders' => function ($q) {
                $q->orderBy('order.id');
            }
        ))->orderBy('customer.id DESC, order.id')->all();
        $this->assertCount(2, $customers);
        $this->assertEquals(2, $customers[0]->id);
        $this->assertEquals(1, $customers[1]->id);
        $this->assertTrue($customers[0]->isRelationPopulated('orders'));
        $this->assertTrue($customers[1]->isRelationPopulated('orders'));
    }

    /**
     * This query will do the same join twice, ensure duplicated JOIN gets removed
     * https://github.com/yiisoft/yii2/pull/2650
     */
    public function testJoinWithVia()
    {
        $rows = Order::find()->joinWith('itemsInOrder1')->joinWith(array(
            'items' => function ($q) {
                $q->orderBy('item.id');
            },
        ))->all();
        $this->assertNotEmpty($rows);
    }

    public function aliasMethodProvider()
    {
        return array(
            array('explicit'), // c
//            ['querysyntax'], // {{@customer}}
//            ['applyAlias'], // $query->applyAlias('customer', 'id') // _aliases are currently not being populated
            // later getRelationAlias() could be added
        );
    }

    /**
     * Tests the alias syntax for joinWith: 'alias' => 'relation'
     * @dataProvider aliasMethodProvider
     * @param string $aliasMethod whether alias is specified explicitly or using the query syntax {{@tablename}}
     */
    public function testJoinWithAlias($aliasMethod)
    {
        // left join and eager loading
        /** @var ActiveQuery $query */
        $query = Order::find()->joinWith(array('customer c'));
        if ($aliasMethod === 'explicit') {
            $orders = $query->orderBy('c.id DESC, order.id')->all();
        } elseif ($aliasMethod === 'querysyntax') {
            $orders = $query->orderBy('{{@customer}}.id DESC, {{@order}}.id')->all();
        } elseif ($aliasMethod === 'applyAlias') {
            $orders = $query->orderBy($query->applyAlias('customer', 'id') . ' DESC,' . $query->applyAlias('order', 'id'))->all();
        }
        $this->assertCount(3, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertEquals(1, $orders[2]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('customer'));
        $this->assertTrue($orders[1]->isRelationPopulated('customer'));
        $this->assertTrue($orders[2]->isRelationPopulated('customer'));

        // inner join filtering and eager loading
        $query = Order::find()->innerJoinWith(array('customer c'));
        if ($aliasMethod === 'explicit') {
            $orders = $query->where('c.id=2')->orderBy('order.id')->all();
        } elseif ($aliasMethod === 'querysyntax') {
            $orders = $query->where('{{@customer}}.id=2')->orderBy('{{@order}}.id')->all();
        } elseif ($aliasMethod === 'applyAlias') {
            $orders = $query->where(array($query->applyAlias('customer', 'id') => 2))->orderBy($query->applyAlias('order', 'id'))->all();
        }
        $this->assertCount(2, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('customer'));
        $this->assertTrue($orders[1]->isRelationPopulated('customer'));

        // inner join filtering without eager loading
        $query = Order::find()->innerJoinWith(array('customer c'), false);
        if ($aliasMethod === 'explicit') {
            $orders = $query->where('c.id=2')->orderBy('order.id')->all();
        } elseif ($aliasMethod === 'querysyntax') {
            $orders = $query->where('{{@customer}}.id=2')->orderBy('{{@order}}.id')->all();
        } elseif ($aliasMethod === 'applyAlias') {
            $orders = $query->where(array($query->applyAlias('customer', 'id') => 2))->orderBy($query->applyAlias('order', 'id'))->all();
        }
        $this->assertCount(2, $orders);
        $this->assertEquals(2, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertFalse($orders[0]->isRelationPopulated('customer'));
        $this->assertFalse($orders[1]->isRelationPopulated('customer'));

        // join with via-relation
        $query = Order::find()->innerJoinWith(array('books b'));
        if ($aliasMethod === 'explicit') {
            $orders = $query->where(array('b.name' => 'Yii 1.1 Application Development Cookbook'))->orderBy('order.id')->all();
        } elseif ($aliasMethod === 'querysyntax') {
            $orders = $query->where(array('{{@item}}.name' => 'Yii 1.1 Application Development Cookbook'))->orderBy('{{@order}}.id')->all();
        } elseif ($aliasMethod === 'applyAlias') {
            $orders = $query->where(array($query->applyAlias('book', 'name') => 'Yii 1.1 Application Development Cookbook'))->orderBy($query->applyAlias('order', 'id'))->all();
        }
        $this->assertCount(2, $orders);
        $this->assertEquals(1, $orders[0]->id);
        $this->assertEquals(3, $orders[1]->id);
        $this->assertTrue($orders[0]->isRelationPopulated('books'));
        $this->assertTrue($orders[1]->isRelationPopulated('books'));
        $this->assertCount(2, $orders[0]->books);
        $this->assertCount(1, $orders[1]->books);


        // joining sub relations
        $query = Order::find()->innerJoinWith(array(
            'items i' => function ($q) use ($aliasMethod) {
                /** @var $q ActiveQuery */
                if ($aliasMethod === 'explicit') {
                    $q->orderBy('i.id');
                } elseif ($aliasMethod === 'querysyntax') {
                    $q->orderBy('{{@item}}.id');
                } elseif ($aliasMethod === 'applyAlias') {
                    $q->orderBy($q->applyAlias('item', 'id'));
                }
            },
            'items.category c' => function ($q) use ($aliasMethod) {
                    /** @var $q ActiveQuery */
                    if ($aliasMethod === 'explicit') {
                        $q->where('c.id = 2');
                    } elseif ($aliasMethod === 'querysyntax') {
                        $q->where('{{@category}}.id = 2');
                    } elseif ($aliasMethod === 'applyAlias') {
                        $q->where(array($q->applyAlias('category', 'id') => 2));
                    }
                },
        ));
        if ($aliasMethod === 'explicit') {
            $orders = $query->orderBy('i.id')->all();
        } elseif ($aliasMethod === 'querysyntax') {
            $orders = $query->orderBy('{{@item}}.id')->all();
        } elseif ($aliasMethod === 'applyAlias') {
            $orders = $query->orderBy($query->applyAlias('item', 'id'))->all();
        }
        $this->assertCount(1, $orders);
        $this->assertTrue($orders[0]->isRelationPopulated('items'));
        $this->assertEquals(2, $orders[0]->id);
        $this->assertCount(3, $orders[0]->items);
        $this->assertTrue($orders[0]->items[0]->isRelationPopulated('category'));
        $this->assertEquals(2, $orders[0]->items[0]->category->id);

        // join with ON condition
        if ($aliasMethod === 'explicit' || $aliasMethod === 'querysyntax') {
            $relationName = 'books' . ucfirst($aliasMethod);
            $orders = Order::find()->joinWith(array("$relationName b"))->orderBy('order.id')->all();
            $this->assertCount(3, $orders);
            $this->assertEquals(1, $orders[0]->id);
            $this->assertEquals(2, $orders[1]->id);
            $this->assertEquals(3, $orders[2]->id);
            $this->assertTrue($orders[0]->isRelationPopulated($relationName));
            $this->assertTrue($orders[1]->isRelationPopulated($relationName));
            $this->assertTrue($orders[2]->isRelationPopulated($relationName));
            $this->assertCount(2, $orders[0]->$relationName);
            $this->assertCount(0, $orders[1]->$relationName);
            $this->assertCount(1, $orders[2]->$relationName);
        }

        // join with ON condition and alias in relation definition
        if ($aliasMethod === 'explicit' || $aliasMethod === 'querysyntax') {
            $relationName = 'books' . ucfirst($aliasMethod) . 'A';
            $orders = Order::find()->joinWith(array("$relationName"))->orderBy('order.id')->all();
            $this->assertCount(3, $orders);
            $this->assertEquals(1, $orders[0]->id);
            $this->assertEquals(2, $orders[1]->id);
            $this->assertEquals(3, $orders[2]->id);
            $this->assertTrue($orders[0]->isRelationPopulated($relationName));
            $this->assertTrue($orders[1]->isRelationPopulated($relationName));
            $this->assertTrue($orders[2]->isRelationPopulated($relationName));
            $this->assertCount(2, $orders[0]->$relationName);
            $this->assertCount(0, $orders[1]->$relationName);
            $this->assertCount(1, $orders[2]->$relationName);
        }

        // join with count and query
        /** @var $query ActiveQuery */
        $query = Order::find()->joinWith(array('customer c'));
        if ($aliasMethod === 'explicit') {
            $count = $query->count('c.id');
        } elseif ($aliasMethod === 'querysyntax') {
            $count = $query->count('{{@customer}}.id');
        } elseif ($aliasMethod === 'applyAlias') {
            $count = $query->count($query->applyAlias('customer', 'id'));
        }
        $this->assertEquals(3, $count);
        $orders = $query->all();
        $this->assertCount(3, $orders);

        // relational query
        /** @var $order Order */
        $order = Order::findOne(1);
        $customerQuery = $order->getCustomer()->innerJoinWith(array('orders o'), false);
        if ($aliasMethod === 'explicit') {
            $customer = $customerQuery->where(array('o.id' => 1))->one();
        } elseif ($aliasMethod === 'querysyntax') {
            $customer = $customerQuery->where(array('{{@order}}.id' => 1))->one();
        } elseif ($aliasMethod === 'applyAlias') {
            $customer = $customerQuery->where(array($query->applyAlias('order', 'id') => 1))->one();
        }
        $this->assertNotNull($customer);
        $this->assertEquals(1, $customer->id);

        // join with sub-relation called inside Closure
        $orders = Order::find()->joinWith(array(
            'items' => function ($q) use ($aliasMethod) {
                /** @var $q ActiveQuery */
                $q->orderBy('item.id');
                $q->joinWith(array('category c'));
                if ($aliasMethod === 'explicit') {
                    $q->where('c.id = 2');
                } elseif ($aliasMethod === 'querysyntax') {
                    $q->where('{{@category}}.id = 2');
                } elseif ($aliasMethod === 'applyAlias') {
                    $q->where(array($q->applyAlias('category', 'id') => 2));
                }
            },
        ))->orderBy('order.id')->all();
        $this->assertCount(1, $orders);
        $this->assertTrue($orders[0]->isRelationPopulated('items'));
        $this->assertEquals(2, $orders[0]->id);
        $this->assertCount(3, $orders[0]->items);
        $this->assertTrue($orders[0]->items[0]->isRelationPopulated('category'));
        $this->assertEquals(2, $orders[0]->items[0]->category->id);

    }

//    public function testJoinWithSameTable()
//    {
//        // join with the same table but different aliases
//        // alias is defined in the relation definition
//        // without eager loading
//        $query = Order::find()
//            ->joinWith('bookItems', false)
//            ->joinWith('movieItems', false)
//            ->where(['movies.name' => 'Toy Story']);
//        $orders = $query->all();
//        $this->assertCount(1, $orders, $query->createCommand()->rawSql . print_r($orders, true));
//        $this->assertEquals(2, $orders[0]->id);
//        $this->assertFalse($orders[0]->isRelationPopulated('bookItems'));
//        $this->assertFalse($orders[0]->isRelationPopulated('movieItems'));
//        // with eager loading
//        $query = Order::find()
//            ->joinWith('bookItems', true)
//            ->joinWith('movieItems', true)
//            ->where(['movies.name' => 'Toy Story']);
//        $orders = $query->all();
//        $this->assertCount(1, $orders, $query->createCommand()->rawSql . print_r($orders, true));
//        $this->assertEquals(2, $orders[0]->id);
//        $this->assertTrue($orders[0]->isRelationPopulated('bookItems'));
//        $this->assertTrue($orders[0]->isRelationPopulated('movieItems'));
//        $this->assertCount(0, $orders[0]->bookItems);
//        $this->assertCount(3, $orders[0]->movieItems);
//
//        // join with the same table but different aliases
//        // alias is defined in the call to joinWith()
//        // without eager loading
//        $query = Order::find()
//            ->joinWith(['itemsIndexed books' => function($q) { $q->onCondition('books.category_id = 1'); }], false)
//            ->joinWith(['itemsIndexed movies' => function($q) { $q->onCondition('movies.category_id = 2'); }], false)
//            ->where(['movies.name' => 'Toy Story']);
//        $orders = $query->all();
//        $this->assertCount(1, $orders, $query->createCommand()->rawSql . print_r($orders, true));
//        $this->assertEquals(2, $orders[0]->id);
//        $this->assertFalse($orders[0]->isRelationPopulated('itemsIndexed'));
//        // with eager loading, only for one relation as it would be overwritten otherwise.
//        $query = Order::find()
//            ->joinWith(['itemsIndexed books' => function($q) { $q->onCondition('books.category_id = 1'); }], false)
//            ->joinWith(['itemsIndexed movies' => function($q) { $q->onCondition('movies.category_id = 2'); }], true)
//            ->where(['movies.name' => 'Toy Story']);
//        $orders = $query->all();
//        $this->assertCount(1, $orders, $query->createCommand()->rawSql . print_r($orders, true));
//        $this->assertEquals(2, $orders[0]->id);
//        $this->assertTrue($orders[0]->isRelationPopulated('itemsIndexed'));
//        $this->assertCount(3, $orders[0]->itemsIndexed);
//        // with eager loading, and the other relation
//        $query = Order::find()
//            ->joinWith(['itemsIndexed books' => function($q) { $q->onCondition('books.category_id = 1'); }], true)
//            ->joinWith(['itemsIndexed movies' => function($q) { $q->onCondition('movies.category_id = 2'); }], false)
//            ->where(['movies.name' => 'Toy Story']);
//        $orders = $query->all();
//        $this->assertCount(1, $orders, $query->createCommand()->rawSql . print_r($orders, true));
//        $this->assertEquals(2, $orders[0]->id);
//        $this->assertTrue($orders[0]->isRelationPopulated('itemsIndexed'));
//        $this->assertCount(0, $orders[0]->itemsIndexed);
//    }
//
//    /**
//     * https://github.com/yiisoft/yii2/issues/10201
//     * https://github.com/yiisoft/yii2/issues/9047
//     */
//    public function testFindCompositeRelationWithJoin()
//    {
//        /* @var $orderItem OrderItem */
//        $orderItem = OrderItem::findOne([1, 1]);
//
//        $orderItemNoJoin = $orderItem->orderItemCompositeNoJoin;
//        $this->assertInstanceOf('yiiunit\data\ar\OrderItem', $orderItemNoJoin);
//
//        $orderItemWithJoin = $orderItem->orderItemCompositeWithJoin;
//        $this->assertInstanceOf('yiiunit\data\ar\OrderItem', $orderItemWithJoin);
//    }
//
//    public function testFindSimpleRelationWithJoin()
//    {
//        /* @var $order Order */
//        $order = Order::findOne(1);
//
//        $customerNoJoin = $order->customer;
//        $this->assertInstanceOf('yiiunit\data\ar\Customer', $customerNoJoin);
//
//        $customerWithJoin = $order->customerJoinedWithProfile;
//        $this->assertInstanceOf('yiiunit\data\ar\Customer', $customerWithJoin);
//
//        $customerWithJoinIndexOrdered = $order->customerJoinedWithProfileIndexOrdered;
//        $this->assertTrue(is_array($customerWithJoinIndexOrdered));
//        $this->assertArrayHasKey('user1', $customerWithJoinIndexOrdered);
//        $this->assertInstanceOf('yiiunit\data\ar\Customer', $customerWithJoinIndexOrdered['user1']);
//    }
//
//    public function tableNameProvider()
//    {
//        return [
//            ['order', 'order_item'],
//            ['order', '{{%order_item}}'],
//            ['{{%order}}', 'order_item'],
//            ['{{%order}}', '{{%order_item}}'],
//        ];
//    }
//
//    /**
//     * Test whether conditions are quoted correctly in conditions where joinWith is used.
//     * @see https://github.com/yiisoft/yii2/issues/11088
//     * @dataProvider tableNameProvider
//     */
//    public function testRelationWhereParams($orderTableName, $orderItemTableName)
//    {
//        Order::$tableName = $orderTableName;
//        OrderItem::$tableName = $orderItemTableName;
//
//        /** @var $order Order */
//        $order = Order::findOne(1);
//        $itemsSQL = $order->getOrderitems()->createCommand()->rawSql;
//        $expectedSQL = $this->replaceQuotes("SELECT * FROM [[order_item]] WHERE [[order_id]]=1");
//        $this->assertEquals($expectedSQL, $itemsSQL);
//
//        $order = Order::findOne(1);
//        $itemsSQL = $order->getOrderItems()->joinWith('item')->createCommand()->rawSql;
//        $expectedSQL = $this->replaceQuotes("SELECT [[order_item]].* FROM [[order_item]] LEFT JOIN [[item]] ON [[order_item]].[[item_id]] = [[item]].[[id]] WHERE [[order_item]].[[order_id]]=1");
//        $this->assertEquals($expectedSQL, $itemsSQL);
//
//        Order::$tableName = null;
//        OrderItem::$tableName = null;
//    }
//
//    public function testAlias()
//    {
//        $query = Order::find();
//        $this->assertNull($query->from);
//
//        $query = Order::find()->alias('o');
//        $this->assertEquals(['o' => Order::tableName()], $query->from);
//
//        $query = Order::find()->alias('o')->alias('ord');
//        $this->assertEquals(['ord' => Order::tableName()], $query->from);
//
//        $query = Order::find()->from([
//            'users',
//            'o' => Order::tableName(),
//        ])->alias('ord');
//        $this->assertEquals([
//            'users',
//            'ord' => Order::tableName(),
//        ], $query->from);
//    }
//
//    public function testInverseOf()
//    {
//        // eager loading: find one and all
//        $customer = Customer::find()->with('orders2')->where(['id' => 1])->one();
//        $this->assertSame($customer->orders2[0]->customer2, $customer);
//        $customers = Customer::find()->with('orders2')->where(['id' => [1, 3]])->all();
//        $this->assertSame($customers[0]->orders2[0]->customer2, $customers[0]);
//        $this->assertEmpty($customers[1]->orders2);
//        // lazy loading
//        $customer = Customer::findOne(2);
//        $orders = $customer->orders2;
//        $this->assertCount(2, $orders);
//        $this->assertSame($customer->orders2[0]->customer2, $customer);
//        $this->assertSame($customer->orders2[1]->customer2, $customer);
//        // ad-hoc lazy loading
//        $customer = Customer::findOne(2);
//        $orders = $customer->getOrders2()->all();
//        $this->assertCount(2, $orders);
//        $this->assertTrue($orders[0]->isRelationPopulated('customer2'), 'inverse relation did not populate the relation');
//        $this->assertTrue($orders[1]->isRelationPopulated('customer2'), 'inverse relation did not populate the relation');
//        $this->assertSame($orders[0]->customer2, $customer);
//        $this->assertSame($orders[1]->customer2, $customer);
//
//        // the other way around
//        $customer = Customer::find()->with('orders2')->where(['id' => 1])->asArray()->one();
//        $this->assertSame($customer['orders2'][0]['customer2']['id'], $customer['id']);
//        $customers = Customer::find()->with('orders2')->where(['id' => [1, 3]])->asArray()->all();
//        $this->assertSame($customer['orders2'][0]['customer2']['id'], $customers[0]['id']);
//        $this->assertEmpty($customers[1]['orders2']);
//
//        $orders = Order::find()->with('customer2')->where(['id' => 1])->all();
//        $this->assertSame($orders[0]->customer2->orders2, [$orders[0]]);
//        $order = Order::find()->with('customer2')->where(['id' => 1])->one();
//        $this->assertSame($order->customer2->orders2, [$order]);
//
//        $orders = Order::find()->with('customer2')->where(['id' => 1])->asArray()->all();
//        $this->assertSame($orders[0]['customer2']['orders2'][0]['id'], $orders[0]['id']);
//        $order = Order::find()->with('customer2')->where(['id' => 1])->asArray()->one();
//        $this->assertSame($order['customer2']['orders2'][0]['id'], $orders[0]['id']);
//
//        $orders = Order::find()->with('customer2')->where(['id' => [1, 3]])->all();
//        $this->assertSame($orders[0]->customer2->orders2, [$orders[0]]);
//        $this->assertSame($orders[1]->customer2->orders2, [$orders[1]]);
//
//        $orders = Order::find()->with('customer2')->where(['id' => [2, 3]])->orderBy('id')->all();
//        $this->assertSame($orders[0]->customer2->orders2, $orders);
//        $this->assertSame($orders[1]->customer2->orders2, $orders);
//
//        $orders = Order::find()->with('customer2')->where(['id' => [2, 3]])->orderBy('id')->asArray()->all();
//        $this->assertSame($orders[0]['customer2']['orders2'][0]['id'], $orders[0]['id']);
//        $this->assertSame($orders[0]['customer2']['orders2'][1]['id'], $orders[1]['id']);
//        $this->assertSame($orders[1]['customer2']['orders2'][0]['id'], $orders[0]['id']);
//        $this->assertSame($orders[1]['customer2']['orders2'][1]['id'], $orders[1]['id']);
//    }
//
//    public function testInverseOfDynamic()
//    {
//        $customer = Customer::findOne(1);
//
//        // request the inverseOf relation without explicitly (eagerly) loading it
//        $orders2 = $customer->getOrders2()->all();
//        $this->assertSame($customer, $orders2[0]->customer2);
//
//        $orders2 = $customer->getOrders2()->one();
//        $this->assertSame($customer, $orders2->customer2);
//
//        // request the inverseOf relation while also explicitly eager loading it (while possible, this is of course redundant)
//        $orders2 = $customer->getOrders2()->with('customer2')->all();
//        $this->assertSame($customer, $orders2[0]->customer2);
//
//        $orders2 = $customer->getOrders2()->with('customer2')->one();
//        $this->assertSame($customer, $orders2->customer2);
//
//        // request the inverseOf relation as array
//        $orders2 = $customer->getOrders2()->asArray()->all();
//        $this->assertEquals($customer['id'], $orders2[0]['customer2']['id']);
//
//        $orders2 = $customer->getOrders2()->asArray()->one();
//        $this->assertEquals($customer['id'], $orders2['customer2']['id']);
//    }
//
//    public function testDefaultValues()
//    {
//        $model = new Type();
//        $model->loadDefaultValues();
//        $this->assertEquals(1, $model->int_col2);
//        $this->assertEquals('something', $model->char_col2);
//        $this->assertEquals(1.23, $model->float_col2);
//        $this->assertEquals(33.22, $model->numeric_col);
//        $this->assertEquals(true, $model->bool_col2);
//
//        if ($this instanceof CubridActiveRecordTest) {
//            // cubrid has non-standard timestamp representation
//            $this->assertEquals('12:00:00 AM 01/01/2002', $model->time);
//        } else {
//            $this->assertEquals('2002-01-01 00:00:00', $model->time);
//        }
//
//        $model = new Type();
//        $model->char_col2 = 'not something';
//
//        $model->loadDefaultValues();
//        $this->assertEquals('not something', $model->char_col2);
//
//        $model = new Type();
//        $model->char_col2 = 'not something';
//
//        $model->loadDefaultValues(false);
//        $this->assertEquals('something', $model->char_col2);
//    }
//
//    public function testUnlinkAllViaTable()
//    {
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//        /* @var $orderItemClass \yii\db\ActiveRecord */
//        $orderItemClass = $this->getOrderItemClass();
//        /* @var $itemClass \yii\db\ActiveRecord */
//        $itemClass = $this->getItemClass();
//        /* @var $orderItemsWithNullFKClass \yii\db\ActiveRecord */
//        $orderItemsWithNullFKClass = $this->getOrderItemWithNullFKmClass();
//
//        // via table with delete
//        /* @var $order  Order */
//        $order = $orderClass::findOne(1);
//        $this->assertCount(2, $order->booksViaTable);
//        $orderItemCount = $orderItemClass::find()->count();
//        $this->assertEquals(5, $itemClass::find()->count());
//        $order->unlinkAll('booksViaTable', true);
//        $this->afterSave();
//        $this->assertEquals(5, $itemClass::find()->count());
//        $this->assertEquals($orderItemCount - 2, $orderItemClass::find()->count());
//        $this->assertCount(0, $order->booksViaTable);
//
//        // via table without delete
//        $this->assertCount(2, $order->booksWithNullFKViaTable);
//        $orderItemCount = $orderItemsWithNullFKClass::find()->count();
//        $this->assertEquals(5, $itemClass::find()->count());
//        $order->unlinkAll('booksWithNullFKViaTable', false);
//        $this->assertCount(0, $order->booksWithNullFKViaTable);
//        $this->assertEquals(2, $orderItemsWithNullFKClass::find()->where(['AND', ['item_id' => [1, 2]], ['order_id' => null]])->count());
//        $this->assertEquals($orderItemCount, $orderItemsWithNullFKClass::find()->count());
//        $this->assertEquals(5, $itemClass::find()->count());
//    }
//
//    public function testCastValues()
//    {
//        $model = new Type();
//        $model->int_col = 123;
//        $model->int_col2 = 456;
//        $model->smallint_col = 42;
//        $model->char_col = '1337';
//        $model->char_col2 = 'test';
//        $model->char_col3 = 'test123';
//        $model->float_col = 3.742;
//        $model->float_col2 = 42.1337;
//        $model->bool_col = true;
//        $model->bool_col2 = false;
//        $model->save(false);
//
//        /* @var $model Type */
//        $model = Type::find()->one();
//        $this->assertSame(123, $model->int_col);
//        $this->assertSame(456, $model->int_col2);
//        $this->assertSame(42, $model->smallint_col);
//        $this->assertSame('1337', trim($model->char_col));
//        $this->assertSame('test', $model->char_col2);
//        $this->assertSame('test123', $model->char_col3);
////        $this->assertSame(1337.42, $model->float_col);
////        $this->assertSame(42.1337, $model->float_col2);
////        $this->assertSame(true, $model->bool_col);
////        $this->assertSame(false, $model->bool_col2);
//    }
//
//    public function testIssues()
//    {
//        // https://github.com/yiisoft/yii2/issues/4938
//        $category = Category::findOne(2);
//        $this->assertInstanceOf(Category::className(), $category);
//        $this->assertEquals(3, $category->getItems()->count());
//        $this->assertEquals(1, $category->getLimitedItems()->count());
//        $this->assertEquals(1, $category->getLimitedItems()->distinct(true)->count());
//
//        // https://github.com/yiisoft/yii2/issues/3197
//        $orders = Order::find()->with('orderItems')->orderBy('id')->all();
//        $this->assertCount(3, $orders);
//        $this->assertCount(2, $orders[0]->orderItems);
//        $this->assertCount(3, $orders[1]->orderItems);
//        $this->assertCount(1, $orders[2]->orderItems);
//        $orders = Order::find()->with(['orderItems' => function ($q) { $q->indexBy('item_id'); }])->orderBy('id')->all();
//        $this->assertCount(3, $orders);
//        $this->assertCount(2, $orders[0]->orderItems);
//        $this->assertCount(3, $orders[1]->orderItems);
//        $this->assertCount(1, $orders[2]->orderItems);
//
//        // https://github.com/yiisoft/yii2/issues/8149
//        $model = new Customer();
//        $model->name = 'test';
//        $model->email = 'test';
//        $model->save(false);
//        $model->updateCounters(['status' => 1]);
//        $this->assertEquals(1, $model->status);
//    }
//
//    public function testPopulateRecordCallWhenQueryingOnParentClass()
//    {
//        (new Cat())->save(false);
//        (new Dog())->save(false);
//
//        $animal = Animal::find()->where(['type' => Dog::className()])->one();
//        $this->assertEquals('bark', $animal->getDoes());
//
//        $animal = Animal::find()->where(['type' => Cat::className()])->one();
//        $this->assertEquals('meow', $animal->getDoes());
//    }
//
//    public function testSaveEmpty()
//    {
//        $record = new NullValues;
//        $this->assertTrue($record->save(false));
//        $this->assertEquals(1, $record->id);
//    }
//
//    public function testOptimisticLock()
//    {
//        /* @var $record Document */
//
//        $record = Document::findOne(1);
//        $record->content = 'New Content';
//        $record->save(false);
//        $this->assertEquals(1, $record->version);
//
//        $record = Document::findOne(1);
//        $record->content = 'Rewrite attempt content';
//        $record->version = 0;
//        $this->expectException('yii\db\StaleObjectException');
//        $record->save(false);
//    }
//
//    public function testPopulateWithoutPk()
//    {
//        // tests with single pk asArray
//        $aggregation = Customer::find()
//            ->select(['{{customer}}.[[status]]', 'SUM({{order}}.[[total]]) AS [[sumtotal]]'])
//            ->joinWith('ordersPlain', false)
//            ->groupBy('{{customer}}.[[status]]')
//            ->orderBy('status')
//            ->asArray()
//            ->all();
//
//        $expected = [
//            [
//                'status' => 1,
//                'sumtotal' => 183,
//            ],
//            [
//                'status' => 2,
//                'sumtotal' => 0,
//            ],
//        ];
//        $this->assertEquals($expected, $aggregation);
//
//        // tests with single pk with Models
//        $aggregation = Customer::find()
//            ->select(['{{customer}}.[[status]]', 'SUM({{order}}.[[total]]) AS [[sumTotal]]'])
//            ->joinWith('ordersPlain', false)
//            ->groupBy('{{customer}}.[[status]]')
//            ->orderBy('status')
//            ->all();
//        $this->assertCount(2, $aggregation);
//        $this->assertContainsOnlyInstancesOf(Customer::className(), $aggregation);
//        foreach ($aggregation as $item) {
//            if ($item->status == 1) {
//                $this->assertEquals(183, $item->sumTotal);
//            } elseif ($item->status == 2) {
//                $this->assertEquals(0, $item->sumTotal);
//            }
//        }
//
//        // tests with composite pk asArray
//        $aggregation = OrderItem::find()
//            ->select(['[[order_id]]', 'SUM([[subtotal]]) AS [[subtotal]]'])
//            ->joinWith('order', false)
//            ->groupBy('[[order_id]]')
//            ->orderBy('[[order_id]]')
//            ->asArray()
//            ->all();
//        $expected = [
//            [
//                'order_id' => 1,
//                'subtotal' => 70,
//            ],
//            [
//                'order_id' => 2,
//                'subtotal' => 33,
//            ],
//            [
//                'order_id' => 3,
//                'subtotal' => 40,
//            ],
//        ];
//        $this->assertEquals($expected, $aggregation);
//
//        // tests with composite pk with Models
//        $aggregation = OrderItem::find()
//            ->select(['[[order_id]]', 'SUM([[subtotal]]) AS [[subtotal]]'])
//            ->joinWith('order', false)
//            ->groupBy('[[order_id]]')
//            ->orderBy('[[order_id]]')
//            ->all();
//        $this->assertCount(3, $aggregation);
//        $this->assertContainsOnlyInstancesOf(OrderItem::className(), $aggregation);
//        foreach ($aggregation as $item) {
//            if ($item->order_id == 1) {
//                $this->assertEquals(70, $item->subtotal);
//            } elseif ($item->order_id == 2) {
//                $this->assertEquals(33, $item->subtotal);
//            } elseif ($item->order_id == 3) {
//                $this->assertEquals(40, $item->subtotal);
//            }
//        }
//    }
//
//    /**
//     * https://github.com/yiisoft/yii2/issues/9006
//     */
//    public function testBit()
//    {
//        $falseBit = BitValues::findOne(1);
//        $this->assertEquals(false, $falseBit->val);
//
//        $trueBit = BitValues::findOne(2);
//        $this->assertEquals(true, $trueBit->val);
//    }
//
//    public function testLinkWhenRelationIsIndexed2()
//    {
//        $order = Order::find()
//                ->with('orderItems2')
//                ->where(['id' => 1])
//                ->one();
//        $orderItem = new OrderItem([
//            'order_id' => $order->id,
//            'item_id' => 3,
//            'quantity' => 1,
//            'subtotal' => 10.0,
//        ]);
//        $order->link('orderItems2', $orderItem);
//        $this->assertTrue(isset($order->orderItems2['3']));
//    }
//
//    public function testLinkWhenRelationIsIndexed3()
//    {
//        $order = Order::find()
//                ->with('orderItems3')
//                ->where(['id' => 1])
//                ->one();
//        $orderItem = new OrderItem([
//            'order_id' => $order->id,
//            'item_id' => 3,
//            'quantity' => 1,
//            'subtotal' => 10.0,
//        ]);
//        $order->link('orderItems3', $orderItem);
//        $this->assertTrue(isset($order->orderItems3['1_3']));
//    }
//
//    public function testUpdateAttributes()
//    {
//        $order = Order::findOne(1);
//        $newTotal = 978;
//        $this->assertSame(1, $order->updateAttributes(['total' => $newTotal]));
//        $this->assertEquals($newTotal, $order->total);
//        $order = Order::findOne(1);
//        $this->assertEquals($newTotal, $order->total);
//
//        // @see https://github.com/yiisoft/yii2/issues/12143
//        $newOrder = new Order();
//        $this->assertTrue($newOrder->getIsNewRecord());
//        $newTotal = 200;
//        $this->assertSame(0, $newOrder->updateAttributes(['total' => $newTotal]));
//        $this->assertTrue($newOrder->getIsNewRecord());
//        $this->assertEquals($newTotal, $newOrder->total);
//    }
//
//    public function testEmulateExecution()
//    {
//        $this->assertGreaterThan(0, Customer::find()->count());
//
//        $rows = Customer::find()
//            ->emulateExecution()
//            ->all();
//        $this->assertSame([], $rows);
//
//        $row = Customer::find()
//            ->emulateExecution()
//            ->one();
//        $this->assertNull($row);
//
//        $exists = Customer::find()
//            ->emulateExecution()
//            ->exists();
//        $this->assertFalse($exists);
//
//        $count = Customer::find()
//            ->emulateExecution()
//            ->count();
//        $this->assertSame(0, $count);
//
//        $sum = Customer::find()
//            ->emulateExecution()
//            ->sum('id');
//        $this->assertSame(0, $sum);
//
//        $sum = Customer::find()
//            ->emulateExecution()
//            ->average('id');
//        $this->assertSame(0, $sum);
//
//        $max = Customer::find()
//            ->emulateExecution()
//            ->max('id');
//        $this->assertNull($max);
//
//        $min = Customer::find()
//            ->emulateExecution()
//            ->min('id');
//        $this->assertNull($min);
//
//        $scalar = Customer::find()
//            ->select(['id'])
//            ->emulateExecution()
//            ->scalar();
//        $this->assertNull($scalar);
//
//        $column = Customer::find()
//            ->select(['id'])
//            ->emulateExecution()
//            ->column();
//        $this->assertSame([], $column);
//    }
//
//    /**
//     * https://github.com/yiisoft/yii2/issues/12213
//     */
//    public function testUnlinkAllOnCondition()
//    {
//        /** @var Category $categoryClass */
//        $categoryClass = $this->getCategoryClass();
//        /** @var Item $itemClass */
//        $itemClass = $this->getItemClass();
//
//        // Ensure there are three items with category_id = 2 in the Items table
//        $itemsCount = $itemClass::find()->where(['category_id' => 2])->count();
//        $this->assertEquals(3, $itemsCount);
//
//        $categoryQuery = $categoryClass::find()->with('limitedItems')->where(['id' => 2]);
//        // Ensure that limitedItems relation returns only one item
//        // (category_id = 2 and id in (1,2,3))
//        $category = $categoryQuery->one();
//        $this->assertCount(1, $category->limitedItems);
//
//        // Unlink all items in the limitedItems relation
//        $category->unlinkAll('limitedItems', true);
//
//        // Make sure that only one item was unlinked
//        $itemsCount = $itemClass::find()->where(['category_id' => 2])->count();
//        $this->assertEquals(2, $itemsCount);
//
//        // Call $categoryQuery again to ensure no items were found
//        $this->assertCount(0, $categoryQuery->one()->limitedItems);
//    }
//
//    /**
//     * https://github.com/yiisoft/yii2/issues/12213
//     */
//    public function testUnlinkAllOnConditionViaTable()
//    {
//        /** @var Order $orderClass */
//        $orderClass = $this->getOrderClass();
//        /** @var Item $itemClass */
//        $itemClass = $this->getItemClass();
//
//        // Ensure there are three items with category_id = 2 in the Items table
//        $itemsCount = $itemClass::find()->where(['category_id' => 2])->count();
//        $this->assertEquals(3, $itemsCount);
//
//        $orderQuery = $orderClass::find()->with('limitedItems')->where(['id' => 2]);
//        // Ensure that limitedItems relation returns only one item
//        // (category_id = 2 and id in (4, 5))
//        $category = $orderQuery->one();
//        $this->assertCount(2, $category->limitedItems);
//
//        // Unlink all items in the limitedItems relation
//        $category->unlinkAll('limitedItems', true);
//
//        // Call $orderQuery again to ensure that links are removed
//        $this->assertCount(0, $orderQuery->one()->limitedItems);
//
//        // Make sure that only links were removed, the items were not removed
//        $this->assertEquals(3, $itemClass::find()->where(['category_id' => 2])->count());
//    }
//
//    /**
//     * can be overridden to do things after save()
//     */
//    public function afterSave()
//    {
//    }
//
//    public function testFind()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // find one
//        $result = $customerClass::find();
//        $this->assertInstanceOf('\\yii\\db\\ActiveQueryInterface', $result);
//        $customer = $result->one();
//        $this->assertInstanceOf($customerClass, $customer);
//
//        // find all
//        $customers = $customerClass::find()->all();
//        $this->assertCount(3, $customers);
//        $this->assertInstanceOf($customerClass, $customers[0]);
//        $this->assertInstanceOf($customerClass, $customers[1]);
//        $this->assertInstanceOf($customerClass, $customers[2]);
//
//        // find by a single primary key
//        $customer = $customerClass::findOne(2);
//        $this->assertInstanceOf($customerClass, $customer);
//        $this->assertEquals('user2', $customer->name);
//        $customer = $customerClass::findOne(5);
//        $this->assertNull($customer);
//        $customer = $customerClass::findOne(['id' => [5, 6, 1]]);
//        // can't use assertCount() here since it will count model attributes instead
//        $this->assertEquals(1, count($customer));
//        $customer = $customerClass::find()->where(['id' => [5, 6, 1]])->one();
//        $this->assertNotNull($customer);
//
//        // find by column values
//        $customer = $customerClass::findOne(['id' => 2, 'name' => 'user2']);
//        $this->assertInstanceOf($customerClass, $customer);
//        $this->assertEquals('user2', $customer->name);
//        $customer = $customerClass::findOne(['id' => 2, 'name' => 'user1']);
//        $this->assertNull($customer);
//        $customer = $customerClass::findOne(['id' => 5]);
//        $this->assertNull($customer);
//        $customer = $customerClass::findOne(['name' => 'user5']);
//        $this->assertNull($customer);
//
//        // find by attributes
//        $customer = $customerClass::find()->where(['name' => 'user2'])->one();
//        $this->assertInstanceOf($customerClass, $customer);
//        $this->assertEquals(2, $customer->id);
//
//        // scope
//        $this->assertCount(2, $customerClass::find()->active()->all());
//        $this->assertEquals(2, $customerClass::find()->active()->count());
//    }
//
//    public function testFindAsArray()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        // asArray
//        $customer = $customerClass::find()->where(['id' => 2])->asArray()->one();
//        $this->assertEquals([
//            'id' => 2,
//            'email' => 'user2@example.com',
//            'name' => 'user2',
//            'address' => 'address2',
//            'status' => 1,
//            'profile_id' => null,
//        ], $customer);
//
//        // find all asArray
//        $customers = $customerClass::find()->asArray()->all();
//        $this->assertCount(3, $customers);
//        $this->assertArrayHasKey('id', $customers[0]);
//        $this->assertArrayHasKey('name', $customers[0]);
//        $this->assertArrayHasKey('email', $customers[0]);
//        $this->assertArrayHasKey('address', $customers[0]);
//        $this->assertArrayHasKey('status', $customers[0]);
//        $this->assertArrayHasKey('id', $customers[1]);
//        $this->assertArrayHasKey('name', $customers[1]);
//        $this->assertArrayHasKey('email', $customers[1]);
//        $this->assertArrayHasKey('address', $customers[1]);
//        $this->assertArrayHasKey('status', $customers[1]);
//        $this->assertArrayHasKey('id', $customers[2]);
//        $this->assertArrayHasKey('name', $customers[2]);
//        $this->assertArrayHasKey('email', $customers[2]);
//        $this->assertArrayHasKey('address', $customers[2]);
//        $this->assertArrayHasKey('status', $customers[2]);
//    }
//
//    public function testHasAttribute()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        $customer = new $customerClass;
//        $this->assertTrue($customer->hasAttribute('id'));
//        $this->assertTrue($customer->hasAttribute('email'));
//        $this->assertFalse($customer->hasAttribute(0));
//        $this->assertFalse($customer->hasAttribute(null));
//        $this->assertFalse($customer->hasAttribute(42));
//
//        $customer = $customerClass::findOne(1);
//        $this->assertTrue($customer->hasAttribute('id'));
//        $this->assertTrue($customer->hasAttribute('email'));
//        $this->assertFalse($customer->hasAttribute(0));
//        $this->assertFalse($customer->hasAttribute(null));
//        $this->assertFalse($customer->hasAttribute(42));
//    }
//
//    public function testFindScalar()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // query scalar
//        $customerName = $customerClass::find()->where(['id' => 2])->scalar('name');
//        $this->assertEquals('user2', $customerName);
//        $customerName = $customerClass::find()->where(['status' => 2])->scalar('name');
//        $this->assertEquals('user3', $customerName);
//        $customerName = $customerClass::find()->where(['status' => 2])->scalar('noname');
//        $this->assertNull($customerName);
//        $customerId = $customerClass::find()->where(['status' => 2])->scalar('id');
//        $this->assertEquals(3, $customerId);
//    }
//
//    public function testFindColumn()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $this->assertEquals(['user1', 'user2', 'user3'], $customerClass::find()->orderBy(['name' => SORT_ASC])->column('name'));
//        $this->assertEquals(['user3', 'user2', 'user1'], $customerClass::find()->orderBy(['name' => SORT_DESC])->column('name'));
//    }
//
//    public function testFindIndexBy()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // indexBy
//        $customers = $customerClass::find()->indexBy('name')->orderBy('id')->all();
//        $this->assertCount(3, $customers);
//        $this->assertInstanceOf($customerClass, $customers['user1']);
//        $this->assertInstanceOf($customerClass, $customers['user2']);
//        $this->assertInstanceOf($customerClass, $customers['user3']);
//
//        // indexBy callable
//        $customers = $customerClass::find()->indexBy(function ($customer) {
//            return $customer->id . '-' . $customer->name;
//        })->orderBy('id')->all();
//        $this->assertCount(3, $customers);
//        $this->assertInstanceOf($customerClass, $customers['1-user1']);
//        $this->assertInstanceOf($customerClass, $customers['2-user2']);
//        $this->assertInstanceOf($customerClass, $customers['3-user3']);
//    }
//
//    public function testFindIndexByAsArray()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // indexBy + asArray
//        $customers = $customerClass::find()->asArray()->indexBy('name')->all();
//        $this->assertCount(3, $customers);
//        $this->assertArrayHasKey('id', $customers['user1']);
//        $this->assertArrayHasKey('name', $customers['user1']);
//        $this->assertArrayHasKey('email', $customers['user1']);
//        $this->assertArrayHasKey('address', $customers['user1']);
//        $this->assertArrayHasKey('status', $customers['user1']);
//        $this->assertArrayHasKey('id', $customers['user2']);
//        $this->assertArrayHasKey('name', $customers['user2']);
//        $this->assertArrayHasKey('email', $customers['user2']);
//        $this->assertArrayHasKey('address', $customers['user2']);
//        $this->assertArrayHasKey('status', $customers['user2']);
//        $this->assertArrayHasKey('id', $customers['user3']);
//        $this->assertArrayHasKey('name', $customers['user3']);
//        $this->assertArrayHasKey('email', $customers['user3']);
//        $this->assertArrayHasKey('address', $customers['user3']);
//        $this->assertArrayHasKey('status', $customers['user3']);
//
//        // indexBy callable + asArray
//        $customers = $customerClass::find()->indexBy(function ($customer) {
//            return $customer['id'] . '-' . $customer['name'];
//        })->asArray()->all();
//        $this->assertCount(3, $customers);
//        $this->assertArrayHasKey('id', $customers['1-user1']);
//        $this->assertArrayHasKey('name', $customers['1-user1']);
//        $this->assertArrayHasKey('email', $customers['1-user1']);
//        $this->assertArrayHasKey('address', $customers['1-user1']);
//        $this->assertArrayHasKey('status', $customers['1-user1']);
//        $this->assertArrayHasKey('id', $customers['2-user2']);
//        $this->assertArrayHasKey('name', $customers['2-user2']);
//        $this->assertArrayHasKey('email', $customers['2-user2']);
//        $this->assertArrayHasKey('address', $customers['2-user2']);
//        $this->assertArrayHasKey('status', $customers['2-user2']);
//        $this->assertArrayHasKey('id', $customers['3-user3']);
//        $this->assertArrayHasKey('name', $customers['3-user3']);
//        $this->assertArrayHasKey('email', $customers['3-user3']);
//        $this->assertArrayHasKey('address', $customers['3-user3']);
//        $this->assertArrayHasKey('status', $customers['3-user3']);
//    }
//
//    public function testRefresh()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customer = new $customerClass();
//        $this->assertFalse($customer->refresh());
//
//        $customer = $customerClass::findOne(1);
//        $customer->name = 'to be refreshed';
//        $this->assertTrue($customer->refresh());
//        $this->assertEquals('user1', $customer->name);
//    }
//
//    public function testEquals()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $itemClass \yii\db\ActiveRecord */
//        $itemClass = $this->getItemClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customerA = new $customerClass();
//        $customerB = new $customerClass();
//        $this->assertFalse($customerA->equals($customerB));
//
//        $customerA = new $customerClass();
//        $customerB = new $itemClass();
//        $this->assertFalse($customerA->equals($customerB));
//
//        $customerA = $customerClass::findOne(1);
//        $customerB = $customerClass::findOne(2);
//        $this->assertFalse($customerA->equals($customerB));
//
//        $customerB = $customerClass::findOne(1);
//        $this->assertTrue($customerA->equals($customerB));
//
//        $customerA = $customerClass::findOne(1);
//        $customerB = $itemClass::findOne(1);
//        $this->assertFalse($customerA->equals($customerB));
//    }
//
//    public function testFindCount()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $this->assertEquals(3, $customerClass::find()->count());
//
//        $this->assertEquals(1, $customerClass::find()->where(['id' => 1])->count());
//        $this->assertEquals(2, $customerClass::find()->where(['id' => [1, 2]])->count());
//        $this->assertEquals(2, $customerClass::find()->where(['id' => [1, 2]])->offset(1)->count());
//        $this->assertEquals(2, $customerClass::find()->where(['id' => [1, 2]])->offset(2)->count());
//
//        // limit should have no effect on count()
//        $this->assertEquals(3, $customerClass::find()->limit(1)->count());
//        $this->assertEquals(3, $customerClass::find()->limit(2)->count());
//        $this->assertEquals(3, $customerClass::find()->limit(10)->count());
//        $this->assertEquals(3, $customerClass::find()->offset(2)->limit(2)->count());
//    }
//
//    public function testFindLimit()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // all()
//        $customers = $customerClass::find()->all();
//        $this->assertCount(3, $customers);
//
//        $customers = $customerClass::find()->orderBy('id')->limit(1)->all();
//        $this->assertCount(1, $customers);
//        $this->assertEquals('user1', $customers[0]->name);
//
//        $customers = $customerClass::find()->orderBy('id')->limit(1)->offset(1)->all();
//        $this->assertCount(1, $customers);
//        $this->assertEquals('user2', $customers[0]->name);
//
//        $customers = $customerClass::find()->orderBy('id')->limit(1)->offset(2)->all();
//        $this->assertCount(1, $customers);
//        $this->assertEquals('user3', $customers[0]->name);
//
//        $customers = $customerClass::find()->orderBy('id')->limit(2)->offset(1)->all();
//        $this->assertCount(2, $customers);
//        $this->assertEquals('user2', $customers[0]->name);
//        $this->assertEquals('user3', $customers[1]->name);
//
//        $customers = $customerClass::find()->limit(2)->offset(3)->all();
//        $this->assertCount(0, $customers);
//
//        // one()
//        $customer = $customerClass::find()->orderBy('id')->one();
//        $this->assertEquals('user1', $customer->name);
//
//        $customer = $customerClass::find()->orderBy('id')->offset(0)->one();
//        $this->assertEquals('user1', $customer->name);
//
//        $customer = $customerClass::find()->orderBy('id')->offset(1)->one();
//        $this->assertEquals('user2', $customer->name);
//
//        $customer = $customerClass::find()->orderBy('id')->offset(2)->one();
//        $this->assertEquals('user3', $customer->name);
//
//        $customer = $customerClass::find()->offset(3)->one();
//        $this->assertNull($customer);
//
//    }
//
//    public function testFindComplexCondition()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $this->assertEquals(2, $customerClass::find()->where(['OR', ['name' => 'user1'], ['name' => 'user2']])->count());
//        $this->assertCount(2, $customerClass::find()->where(['OR', ['name' => 'user1'], ['name' => 'user2']])->all());
//
//        $this->assertEquals(2, $customerClass::find()->where(['name' => ['user1', 'user2']])->count());
//        $this->assertCount(2, $customerClass::find()->where(['name' => ['user1', 'user2']])->all());
//
//        $this->assertEquals(1, $customerClass::find()->where(['AND', ['name' => ['user2', 'user3']], ['BETWEEN', 'status', 2, 4]])->count());
//        $this->assertCount(1, $customerClass::find()->where(['AND', ['name' => ['user2', 'user3']], ['BETWEEN', 'status', 2, 4]])->all());
//    }
//
//    public function testFindNullValues()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customer = $customerClass::findOne(2);
//        $customer->name = null;
//        $customer->save(false);
//        $this->afterSave();
//
//        $result = $customerClass::find()->where(['name' => null])->all();
//        $this->assertCount(1, $result);
//        $this->assertEquals(2, reset($result)->primaryKey);
//    }
//
//    public function testExists()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $this->assertTrue($customerClass::find()->where(['id' => 2])->exists());
//        $this->assertFalse($customerClass::find()->where(['id' => 5])->exists());
//        $this->assertTrue($customerClass::find()->where(['name' => 'user1'])->exists());
//        $this->assertFalse($customerClass::find()->where(['name' => 'user5'])->exists());
//
//        $this->assertTrue($customerClass::find()->where(['id' => [2, 3]])->exists());
//        $this->assertTrue($customerClass::find()->where(['id' => [2, 3]])->offset(1)->exists());
//        $this->assertFalse($customerClass::find()->where(['id' => [2, 3]])->offset(2)->exists());
//    }
//
//    public function testFindLazy()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customer = $customerClass::findOne(2);
//        $this->assertFalse($customer->isRelationPopulated('orders'));
//        $orders = $customer->orders;
//        $this->assertTrue($customer->isRelationPopulated('orders'));
//        $this->assertCount(2, $orders);
//        $this->assertCount(1, $customer->relatedRecords);
//
//        // unset
//        unset($customer['orders']);
//        $this->assertFalse($customer->isRelationPopulated('orders'));
//
//        /* @var $customer Customer */
//        $customer = $customerClass::findOne(2);
//        $this->assertFalse($customer->isRelationPopulated('orders'));
//        $orders = $customer->getOrders()->where(['id' => 3])->all();
//        $this->assertFalse($customer->isRelationPopulated('orders'));
//        $this->assertCount(0, $customer->relatedRecords);
//
//        $this->assertCount(1, $orders);
//        $this->assertEquals(3, $orders[0]->id);
//    }
//
//    public function testFindEager()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customers = $customerClass::find()->with('orders')->indexBy('id')->all();
//        ksort($customers);
//        $this->assertCount(3, $customers);
//        $this->assertTrue($customers[1]->isRelationPopulated('orders'));
//        $this->assertTrue($customers[2]->isRelationPopulated('orders'));
//        $this->assertTrue($customers[3]->isRelationPopulated('orders'));
//        $this->assertCount(1, $customers[1]->orders);
//        $this->assertCount(2, $customers[2]->orders);
//        $this->assertCount(0, $customers[3]->orders);
//        // unset
//        unset($customers[1]->orders);
//        $this->assertFalse($customers[1]->isRelationPopulated('orders'));
//
//        $customer = $customerClass::find()->where(['id' => 1])->with('orders')->one();
//        $this->assertTrue($customer->isRelationPopulated('orders'));
//        $this->assertCount(1, $customer->orders);
//        $this->assertCount(1, $customer->relatedRecords);
//
//        // multiple with() calls
//        $orders = $orderClass::find()->with('customer', 'items')->all();
//        $this->assertCount(3, $orders);
//        $this->assertTrue($orders[0]->isRelationPopulated('customer'));
//        $this->assertTrue($orders[0]->isRelationPopulated('items'));
//        $orders = $orderClass::find()->with('customer')->with('items')->all();
//        $this->assertCount(3, $orders);
//        $this->assertTrue($orders[0]->isRelationPopulated('customer'));
//        $this->assertTrue($orders[0]->isRelationPopulated('items'));
//    }
//
//    public function testFindLazyVia()
//    {
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        /* @var $order Order */
//        $order = $orderClass::findOne(1);
//        $this->assertEquals(1, $order->id);
//        $this->assertCount(2, $order->items);
//        $this->assertEquals(1, $order->items[0]->id);
//        $this->assertEquals(2, $order->items[1]->id);
//    }
//
//    public function testFindLazyVia2()
//    {
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        /* @var $order Order */
//        $order = $orderClass::findOne(1);
//        $order->id = 100;
//        $this->assertEquals([], $order->items);
//    }
//
//    public function testFindEagerViaRelation()
//    {
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $orders = $orderClass::find()->with('items')->orderBy('id')->all();
//        $this->assertCount(3, $orders);
//        $order = $orders[0];
//        $this->assertEquals(1, $order->id);
//        $this->assertTrue($order->isRelationPopulated('items'));
//        $this->assertCount(2, $order->items);
//        $this->assertEquals(1, $order->items[0]->id);
//        $this->assertEquals(2, $order->items[1]->id);
//    }
//
//    public function testFindNestedRelation()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customers = $customerClass::find()->with('orders', 'orders.items')->indexBy('id')->all();
//        ksort($customers);
//        $this->assertCount(3, $customers);
//        $this->assertTrue($customers[1]->isRelationPopulated('orders'));
//        $this->assertTrue($customers[2]->isRelationPopulated('orders'));
//        $this->assertTrue($customers[3]->isRelationPopulated('orders'));
//        $this->assertCount(1, $customers[1]->orders);
//        $this->assertCount(2, $customers[2]->orders);
//        $this->assertCount(0, $customers[3]->orders);
//        $this->assertTrue($customers[1]->orders[0]->isRelationPopulated('items'));
//        $this->assertTrue($customers[2]->orders[0]->isRelationPopulated('items'));
//        $this->assertTrue($customers[2]->orders[1]->isRelationPopulated('items'));
//        $this->assertCount(2, $customers[1]->orders[0]->items);
//        $this->assertCount(3, $customers[2]->orders[0]->items);
//        $this->assertCount(1, $customers[2]->orders[1]->items);
//
//        $customers = $customerClass::find()->where(['id' => 1])->with('ordersWithItems')->one();
//        $this->assertTrue($customers->isRelationPopulated('ordersWithItems'));
//        $this->assertCount(1, $customers->ordersWithItems);
//
//        /** @var Order $order */
//        $order = $customers->ordersWithItems[0];
//        $this->assertTrue($order->isRelationPopulated('orderItems'));
//        $this->assertCount(2, $order->orderItems);
//    }
//
//    /**
//     * Ensure ActiveRelationTrait does preserve order of items on find via()
//     * https://github.com/yiisoft/yii2/issues/1310
//     */
//    public function testFindEagerViaRelationPreserveOrder()
//    {
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//
//        /*
//        Item (name, category_id)
//        Order (customer_id, created_at, total)
//        OrderItem (order_id, item_id, quantity, subtotal)
//
//        Result should be the following:
//
//        Order 1: 1, 1325282384, 110.0
//        - orderItems:
//            OrderItem: 1, 1, 1, 30.0
//            OrderItem: 1, 2, 2, 40.0
//        - itemsInOrder:
//            Item 1: 'Agile Web Application Development with Yii1.1 and PHP5', 1
//            Item 2: 'Yii 1.1 Application Development Cookbook', 1
//
//        Order 2: 2, 1325334482, 33.0
//        - orderItems:
//            OrderItem: 2, 3, 1, 8.0
//            OrderItem: 2, 4, 1, 10.0
//            OrderItem: 2, 5, 1, 15.0
//        - itemsInOrder:
//            Item 5: 'Cars', 2
//            Item 3: 'Ice Age', 2
//            Item 4: 'Toy Story', 2
//        Order 3: 2, 1325502201, 40.0
//        - orderItems:
//            OrderItem: 3, 2, 1, 40.0
//        - itemsInOrder:
//            Item 3: 'Ice Age', 2
//         */
//        $orders = $orderClass::find()->with('itemsInOrder1')->orderBy('created_at')->all();
//        $this->assertCount(3, $orders);
//
//        $order = $orders[0];
//        $this->assertEquals(1, $order->id);
//        $this->assertTrue($order->isRelationPopulated('itemsInOrder1'));
//        $this->assertCount(2, $order->itemsInOrder1);
//        $this->assertEquals(1, $order->itemsInOrder1[0]->id);
//        $this->assertEquals(2, $order->itemsInOrder1[1]->id);
//
//        $order = $orders[1];
//        $this->assertEquals(2, $order->id);
//        $this->assertTrue($order->isRelationPopulated('itemsInOrder1'));
//        $this->assertCount(3, $order->itemsInOrder1);
//        $this->assertEquals(5, $order->itemsInOrder1[0]->id);
//        $this->assertEquals(3, $order->itemsInOrder1[1]->id);
//        $this->assertEquals(4, $order->itemsInOrder1[2]->id);
//
//        $order = $orders[2];
//        $this->assertEquals(3, $order->id);
//        $this->assertTrue($order->isRelationPopulated('itemsInOrder1'));
//        $this->assertCount(1, $order->itemsInOrder1);
//        $this->assertEquals(2, $order->itemsInOrder1[0]->id);
//    }
//
//    // different order in via table
//    public function testFindEagerViaRelationPreserveOrderB()
//    {
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//
//        $orders = $orderClass::find()->with('itemsInOrder2')->orderBy('created_at')->all();
//        $this->assertCount(3, $orders);
//
//        $order = $orders[0];
//        $this->assertEquals(1, $order->id);
//        $this->assertTrue($order->isRelationPopulated('itemsInOrder2'));
//        $this->assertCount(2, $order->itemsInOrder2);
//        $this->assertEquals(1, $order->itemsInOrder2[0]->id);
//        $this->assertEquals(2, $order->itemsInOrder2[1]->id);
//
//        $order = $orders[1];
//        $this->assertEquals(2, $order->id);
//        $this->assertTrue($order->isRelationPopulated('itemsInOrder2'));
//        $this->assertCount(3, $order->itemsInOrder2);
//        $this->assertEquals(5, $order->itemsInOrder2[0]->id);
//        $this->assertEquals(3, $order->itemsInOrder2[1]->id);
//        $this->assertEquals(4, $order->itemsInOrder2[2]->id);
//
//        $order = $orders[2];
//        $this->assertEquals(3, $order->id);
//        $this->assertTrue($order->isRelationPopulated('itemsInOrder2'));
//        $this->assertCount(1, $order->itemsInOrder2);
//        $this->assertEquals(2, $order->itemsInOrder2[0]->id);
//    }
//
//    public function testLink()
//    {
//        /* @var $orderClass \yii\db\ActiveRecord */
//        /* @var $itemClass \yii\db\ActiveRecord */
//        /* @var $orderItemClass \yii\db\ActiveRecord */
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        $orderClass = $this->getOrderClass();
//        $orderItemClass = $this->getOrderItemClass();
//        $itemClass = $this->getItemClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customer = $customerClass::findOne(2);
//        $this->assertCount(2, $customer->orders);
//
//        // has many
//        $order = new $orderClass;
//        $order->total = 100;
//        $this->assertTrue($order->isNewRecord);
//        $customer->link('orders', $order);
//        $this->afterSave();
//        $this->assertCount(3, $customer->orders);
//        $this->assertFalse($order->isNewRecord);
//        $this->assertCount(3, $customer->getOrders()->all());
//        $this->assertEquals(2, $order->customer_id);
//
//        // belongs to
//        $order = new $orderClass;
//        $order->total = 100;
//        $this->assertTrue($order->isNewRecord);
//        $customer = $customerClass::findOne(1);
//        $this->assertNull($order->customer);
//        $order->link('customer', $customer);
//        $this->assertFalse($order->isNewRecord);
//        $this->assertEquals(1, $order->customer_id);
//        $this->assertEquals(1, $order->customer->primaryKey);
//
//        // via model
//        $order = $orderClass::findOne(1);
//        $this->assertCount(2, $order->items);
//        $this->assertCount(2, $order->orderItems);
//        $orderItem = $orderItemClass::findOne(['order_id' => 1, 'item_id' => 3]);
//        $this->assertNull($orderItem);
//        $item = $itemClass::findOne(3);
//        $order->link('items', $item, ['quantity' => 10, 'subtotal' => 100]);
//        $this->afterSave();
//        $this->assertCount(3, $order->items);
//        $this->assertCount(3, $order->orderItems);
//        $orderItem = $orderItemClass::findOne(['order_id' => 1, 'item_id' => 3]);
//        $this->assertInstanceOf($orderItemClass, $orderItem);
//        $this->assertEquals(10, $orderItem->quantity);
//        $this->assertEquals(100, $orderItem->subtotal);
//    }
//
//    public function testUnlink()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//        /* @var $orderWithNullFKClass \yii\db\ActiveRecord */
//        $orderWithNullFKClass = $this->getOrderWithNullFKClass();
//        /* @var $orderItemsWithNullFKClass \yii\db\ActiveRecord */
//        $orderItemsWithNullFKClass = $this->getOrderItemWithNullFKmClass();
//
//
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // has many without delete
//        $customer = $customerClass::findOne(2);
//        $this->assertCount(2, $customer->ordersWithNullFK);
//        $customer->unlink('ordersWithNullFK', $customer->ordersWithNullFK[1], false);
//
//        $this->assertCount(1, $customer->ordersWithNullFK);
//        $orderWithNullFK = $orderWithNullFKClass::findOne(3);
//
//        $this->assertEquals(3, $orderWithNullFK->id);
//        $this->assertNull($orderWithNullFK->customer_id);
//
//        // has many with delete
//        $customer = $customerClass::findOne(2);
//        $this->assertCount(2, $customer->orders);
//        $customer->unlink('orders', $customer->orders[1], true);
//        $this->afterSave();
//
//        $this->assertCount(1, $customer->orders);
//        $this->assertNull($orderClass::findOne(3));
//
//        // via model with delete
//        $order = $orderClass::findOne(2);
//        $this->assertCount(3, $order->items);
//        $this->assertCount(3, $order->orderItems);
//        $order->unlink('items', $order->items[2], true);
//        $this->afterSave();
//
//        $this->assertCount(2, $order->items);
//        $this->assertCount(2, $order->orderItems);
//
//        // via model without delete
//        $this->assertCount(3, $order->itemsWithNullFK);
//        $order->unlink('itemsWithNullFK', $order->itemsWithNullFK[2], false);
//        $this->afterSave();
//
//        $this->assertCount(2, $order->itemsWithNullFK);
//        $this->assertCount(2, $order->orderItems);
//    }
//
//    public function testUnlinkAll()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//        /* @var $orderItemClass \yii\db\ActiveRecord */
//        $orderItemClass = $this->getOrderItemClass();
//        /* @var $itemClass \yii\db\ActiveRecord */
//        $itemClass = $this->getItemClass();
//        /* @var $orderWithNullFKClass \yii\db\ActiveRecord */
//        $orderWithNullFKClass = $this->getOrderWithNullFKClass();
//        /* @var $orderItemsWithNullFKClass \yii\db\ActiveRecord */
//        $orderItemsWithNullFKClass = $this->getOrderItemWithNullFKmClass();
//
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // has many with delete
//        $customer = $customerClass::findOne(2);
//        $this->assertCount(2, $customer->orders);
//        $this->assertEquals(3, $orderClass::find()->count());
//        $customer->unlinkAll('orders', true);
//        $this->afterSave();
//        $this->assertEquals(1, $orderClass::find()->count());
//        $this->assertCount(0, $customer->orders);
//
//        $this->assertNull($orderClass::findOne(2));
//        $this->assertNull($orderClass::findOne(3));
//
//
//        // has many without delete
//        $customer = $customerClass::findOne(2);
//        $this->assertCount(2, $customer->ordersWithNullFK);
//        $this->assertEquals(3, $orderWithNullFKClass::find()->count());
//        $customer->unlinkAll('ordersWithNullFK', false);
//        $this->afterSave();
//        $this->assertCount(0, $customer->ordersWithNullFK);
//        $this->assertEquals(3, $orderWithNullFKClass::find()->count());
//        $this->assertEquals(2, $orderWithNullFKClass::find()->where(['AND', ['id' => [2, 3]], ['customer_id' => null]])->count());
//
//
//        // via model with delete
//        /* @var $order Order */
//        $order = $orderClass::findOne(1);
//        $this->assertCount(2, $order->books);
//        $orderItemCount = $orderItemClass::find()->count();
//        $this->assertEquals(5, $itemClass::find()->count());
//        $order->unlinkAll('books', true);
//        $this->afterSave();
//        $this->assertEquals(5, $itemClass::find()->count());
//        $this->assertEquals($orderItemCount - 2, $orderItemClass::find()->count());
//        $this->assertCount(0, $order->books);
//
//        // via model without delete
//        $this->assertCount(2, $order->booksWithNullFK);
//        $orderItemCount = $orderItemsWithNullFKClass::find()->count();
//        $this->assertEquals(5, $itemClass::find()->count());
//        $order->unlinkAll('booksWithNullFK',false);
//        $this->afterSave();
//        $this->assertCount(0, $order->booksWithNullFK);
//        $this->assertEquals(2, $orderItemsWithNullFKClass::find()->where(['AND', ['item_id' => [1, 2]], ['order_id' => null]])->count());
//        $this->assertEquals($orderItemCount, $orderItemsWithNullFKClass::find()->count());
//        $this->assertEquals(5, $itemClass::find()->count());
//
//        // via table is covered in \yiiunit\framework\db\ActiveRecordTest::testUnlinkAllViaTable()
//    }
//
//    public function testUnlinkAllAndConditionSetNull()
//    {
//        /* @var $this TestCase|ActiveRecordTestTrait */
//
//        /* @var $customerClass \yii\db\BaseActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $orderClass \yii\db\BaseActiveRecord */
//        $orderClass = $this->getOrderWithNullFKClass();
//
//        // in this test all orders are owned by customer 1
//        $orderClass::updateAll(['customer_id' => 1]);
//        $this->afterSave();
//
//        $customer = $customerClass::findOne(1);
//        $this->assertCount(3, $customer->ordersWithNullFK);
//        $this->assertCount(1, $customer->expensiveOrdersWithNullFK);
//        $this->assertEquals(3, $orderClass::find()->count());
//        $customer->unlinkAll('expensiveOrdersWithNullFK');
//        $this->assertCount(3, $customer->ordersWithNullFK);
//        $this->assertCount(0, $customer->expensiveOrdersWithNullFK);
//        $this->assertEquals(3, $orderClass::find()->count());
//        $customer = $customerClass::findOne(1);
//        $this->assertCount(2, $customer->ordersWithNullFK);
//        $this->assertCount(0, $customer->expensiveOrdersWithNullFK);
//    }
//
//    public function testUnlinkAllAndConditionDelete()
//    {
//        /* @var $this TestCase|ActiveRecordTestTrait */
//
//        /* @var $customerClass \yii\db\BaseActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $orderClass \yii\db\BaseActiveRecord */
//        $orderClass = $this->getOrderClass();
//
//        // in this test all orders are owned by customer 1
//        $orderClass::updateAll(['customer_id' => 1]);
//        $this->afterSave();
//
//        $customer = $customerClass::findOne(1);
//        $this->assertCount(3, $customer->orders);
//        $this->assertCount(1, $customer->expensiveOrders);
//        $this->assertEquals(3, $orderClass::find()->count());
//        $customer->unlinkAll('expensiveOrders', true);
//        $this->assertCount(3, $customer->orders);
//        $this->assertCount(0, $customer->expensiveOrders);
//        $this->assertEquals(2, $orderClass::find()->count());
//        $customer = $customerClass::findOne(1);
//        $this->assertCount(2, $customer->orders);
//        $this->assertCount(0, $customer->expensiveOrders);
//    }
//
//    public static $afterSaveNewRecord;
//    public static $afterSaveInsert;
//
//    public function testInsert()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customer = new $customerClass;
//        $customer->email = 'user4@example.com';
//        $customer->name = 'user4';
//        $customer->address = 'address4';
//
//        $this->assertNull($customer->id);
//        $this->assertTrue($customer->isNewRecord);
//        static::$afterSaveNewRecord = null;
//        static::$afterSaveInsert = null;
//
//        $customer->save();
//        $this->afterSave();
//
//        $this->assertNotNull($customer->id);
//        $this->assertFalse(static::$afterSaveNewRecord);
//        $this->assertTrue(static::$afterSaveInsert);
//        $this->assertFalse($customer->isNewRecord);
//    }
//
//    public function testExplicitPkOnAutoIncrement()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customer = new $customerClass;
//        $customer->id = 1337;
//        $customer->email = 'user1337@example.com';
//        $customer->name = 'user1337';
//        $customer->address = 'address1337';
//
//        $this->assertTrue($customer->isNewRecord);
//        $customer->save();
//        $this->afterSave();
//
//        $this->assertEquals(1337, $customer->id);
//        $this->assertFalse($customer->isNewRecord);
//    }
//
//    public function testUpdate()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // save
//        /* @var $customer Customer */
//        $customer = $customerClass::findOne(2);
//        $this->assertInstanceOf($customerClass, $customer);
//        $this->assertEquals('user2', $customer->name);
//        $this->assertFalse($customer->isNewRecord);
//        static::$afterSaveNewRecord = null;
//        static::$afterSaveInsert = null;
//        $this->assertEmpty($customer->dirtyAttributes);
//
//        $customer->name = 'user2x';
//        $customer->save();
//        $this->afterSave();
//        $this->assertEquals('user2x', $customer->name);
//        $this->assertFalse($customer->isNewRecord);
//        $this->assertFalse(static::$afterSaveNewRecord);
//        $this->assertFalse(static::$afterSaveInsert);
//        $customer2 = $customerClass::findOne(2);
//        $this->assertEquals('user2x', $customer2->name);
//
//        // updateAll
//        $customer = $customerClass::findOne(3);
//        $this->assertEquals('user3', $customer->name);
//        $ret = $customerClass::updateAll(['name' => 'temp'], ['id' => 3]);
//        $this->afterSave();
//        $this->assertEquals(1, $ret);
//        $customer = $customerClass::findOne(3);
//        $this->assertEquals('temp', $customer->name);
//
//        $ret = $customerClass::updateAll(['name' => 'tempX']);
//        $this->afterSave();
//        $this->assertEquals(3, $ret);
//
//        $ret = $customerClass::updateAll(['name' => 'temp'], ['name' => 'user6']);
//        $this->afterSave();
//        $this->assertEquals(0, $ret);
//    }
//
//    public function testUpdateAttributes()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        /* @var $customer Customer */
//        $customer = $customerClass::findOne(2);
//        $this->assertInstanceOf($customerClass, $customer);
//        $this->assertEquals('user2', $customer->name);
//        $this->assertFalse($customer->isNewRecord);
//        static::$afterSaveNewRecord = null;
//        static::$afterSaveInsert = null;
//
//        $customer->updateAttributes(['name' => 'user2x']);
//        $this->afterSave();
//        $this->assertEquals('user2x', $customer->name);
//        $this->assertFalse($customer->isNewRecord);
//        $this->assertNull(static::$afterSaveNewRecord);
//        $this->assertNull(static::$afterSaveInsert);
//        $customer2 = $customerClass::findOne(2);
//        $this->assertEquals('user2x', $customer2->name);
//
//        $customer = $customerClass::findOne(1);
//        $this->assertEquals('user1', $customer->name);
//        $this->assertEquals(1, $customer->status);
//        $customer->name = 'user1x';
//        $customer->status = 2;
//        $customer->updateAttributes(['name']);
//        $this->assertEquals('user1x', $customer->name);
//        $this->assertEquals(2, $customer->status);
//        $customer = $customerClass::findOne(1);
//        $this->assertEquals('user1x', $customer->name);
//        $this->assertEquals(1, $customer->status);
//    }
//
//    public function testUpdateCounters()
//    {
//        /* @var $orderItemClass \yii\db\ActiveRecord */
//        $orderItemClass = $this->getOrderItemClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // updateCounters
//        $pk = ['order_id' => 2, 'item_id' => 4];
//        $orderItem = $orderItemClass::findOne($pk);
//        $this->assertEquals(1, $orderItem->quantity);
//        $ret = $orderItem->updateCounters(['quantity' => -1]);
//        $this->afterSave();
//        $this->assertEquals(1, $ret);
//        $this->assertEquals(0, $orderItem->quantity);
//        $orderItem = $orderItemClass::findOne($pk);
//        $this->assertEquals(0, $orderItem->quantity);
//
//        // updateAllCounters
//        $pk = ['order_id' => 1, 'item_id' => 2];
//        $orderItem = $orderItemClass::findOne($pk);
//        $this->assertEquals(2, $orderItem->quantity);
//        $ret = $orderItemClass::updateAllCounters([
//            'quantity' => 3,
//            'subtotal' => -10,
//        ], $pk);
//        $this->afterSave();
//        $this->assertEquals(1, $ret);
//        $orderItem = $orderItemClass::findOne($pk);
//        $this->assertEquals(5, $orderItem->quantity);
//        $this->assertEquals(30, $orderItem->subtotal);
//    }
//
//    public function testDelete()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        // delete
//        $customer = $customerClass::findOne(2);
//        $this->assertInstanceOf($customerClass, $customer);
//        $this->assertEquals('user2', $customer->name);
//        $customer->delete();
//        $this->afterSave();
//        $customer = $customerClass::findOne(2);
//        $this->assertNull($customer);
//
//        // deleteAll
//        $customers = $customerClass::find()->all();
//        $this->assertCount(2, $customers);
//        $ret = $customerClass::deleteAll();
//        $this->afterSave();
//        $this->assertEquals(2, $ret);
//        $customers = $customerClass::find()->all();
//        $this->assertCount(0, $customers);
//
//        $ret = $customerClass::deleteAll();
//        $this->afterSave();
//        $this->assertEquals(0, $ret);
//    }
//
//    /**
//     * Some PDO implementations(e.g. cubrid) do not support boolean values.
//     * Make sure this does not affect AR layer.
//     */
//    public function testBooleanAttribute()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//        $customer = new $customerClass();
//        $customer->name = 'boolean customer';
//        $customer->email = 'mail@example.com';
//        $customer->status = true;
//        $customer->save(false);
//
//        $customer->refresh();
//        $this->assertEquals(1, $customer->status);
//
//        $customer->status = false;
//        $customer->save(false);
//
//        $customer->refresh();
//        $this->assertEquals(0, $customer->status);
//
//        $customers = $customerClass::find()->where(['status' => true])->all();
//        $this->assertCount(2, $customers);
//
//        $customers = $customerClass::find()->where(['status' => false])->all();
//        $this->assertCount(1, $customers);
//    }
//
//    public function testAfterFind()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $orderClass BaseActiveRecord */
//        $orderClass = $this->getOrderClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//
//        $afterFindCalls = [];
//        Event::on(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_FIND, function ($event) use (&$afterFindCalls) {
//            /* @var $ar BaseActiveRecord */
//            $ar = $event->sender;
//            $afterFindCalls[] = [get_class($ar), $ar->getIsNewRecord(), $ar->getPrimaryKey(), $ar->isRelationPopulated('orders')];
//        });
//
//        $customer = $customerClass::findOne(1);
//        $this->assertNotNull($customer);
//        $this->assertEquals([[$customerClass, false, 1, false]], $afterFindCalls);
//        $afterFindCalls = [];
//
//        $customer = $customerClass::find()->where(['id' => 1])->one();
//        $this->assertNotNull($customer);
//        $this->assertEquals([[$customerClass, false, 1, false]], $afterFindCalls);
//        $afterFindCalls = [];
//
//        $customer = $customerClass::find()->where(['id' => 1])->all();
//        $this->assertNotNull($customer);
//        $this->assertEquals([[$customerClass, false, 1, false]], $afterFindCalls);
//        $afterFindCalls = [];
//
//        $customer = $customerClass::find()->where(['id' => 1])->with('orders')->all();
//        $this->assertNotNull($customer);
//        $this->assertEquals([
//            [$this->getOrderClass(), false, 1, false],
//            [$customerClass, false, 1, true],
//        ], $afterFindCalls);
//        $afterFindCalls = [];
//
//        if ($this instanceof \yiiunit\extensions\redis\ActiveRecordTest) { // TODO redis does not support orderBy() yet
//            $customer = $customerClass::find()->where(['id' => [1, 2]])->with('orders')->all();
//        } else {
//            // orderBy is needed to avoid random test failure
//            $customer = $customerClass::find()->where(['id' => [1, 2]])->with('orders')->orderBy('name')->all();
//        }
//        $this->assertNotNull($customer);
//        $this->assertEquals([
//            [$orderClass, false, 1, false],
//            [$orderClass, false, 2, false],
//            [$orderClass, false, 3, false],
//            [$customerClass, false, 1, true],
//            [$customerClass, false, 2, true],
//        ], $afterFindCalls);
//        $afterFindCalls = [];
//
//        Event::off(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_FIND);
//    }
//
//    public function testAfterRefresh()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//
//        $afterRefreshCalls = [];
//        Event::on(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_REFRESH, function ($event) use (&$afterRefreshCalls) {
//            /* @var $ar BaseActiveRecord */
//            $ar = $event->sender;
//            $afterRefreshCalls[] = [get_class($ar), $ar->getIsNewRecord(), $ar->getPrimaryKey(), $ar->isRelationPopulated('orders')];
//        });
//
//        $customer = $customerClass::findOne(1);
//        $this->assertNotNull($customer);
//        $customer->refresh();
//        $this->assertEquals([[$customerClass, false, 1, false]], $afterRefreshCalls);
//        $afterRefreshCalls = [];
//        Event::off(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_REFRESH);
//    }
//
//    public function testFindEmptyInCondition()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        /* @var $this TestCase|ActiveRecordTestTrait */
//
//        $customers = $customerClass::find()->where(['id' => [1]])->all();
//        $this->assertCount(1, $customers);
//
//        $customers = $customerClass::find()->where(['id' => []])->all();
//        $this->assertCount(0, $customers);
//
//        $customers = $customerClass::find()->where(['IN', 'id', [1]])->all();
//        $this->assertCount(1, $customers);
//
//        $customers = $customerClass::find()->where(['IN', 'id', []])->all();
//        $this->assertCount(0, $customers);
//    }
//
//    public function testFindEagerIndexBy()
//    {
//        /* @var $this TestCase|ActiveRecordTestTrait */
//
//        /* @var $orderClass \yii\db\ActiveRecord */
//        $orderClass = $this->getOrderClass();
//
//        /* @var $order Order */
//        $order = $orderClass::find()->with('itemsIndexed')->where(['id' => 1])->one();
//        $this->assertTrue($order->isRelationPopulated('itemsIndexed'));
//        $items = $order->itemsIndexed;
//        $this->assertCount(2, $items);
//        $this->assertTrue(isset($items[1]));
//        $this->assertTrue(isset($items[2]));
//
//        /* @var $order Order */
//        $order = $orderClass::find()->with('itemsIndexed')->where(['id' => 2])->one();
//        $this->assertTrue($order->isRelationPopulated('itemsIndexed'));
//        $items = $order->itemsIndexed;
//        $this->assertCount(3, $items);
//        $this->assertTrue(isset($items[3]));
//        $this->assertTrue(isset($items[4]));
//        $this->assertTrue(isset($items[5]));
//    }
//
//    public function testAttributeAccess()
//    {
//        /* @var $customerClass \yii\db\ActiveRecord */
//        $customerClass = $this->getCustomerClass();
//        $model = new $customerClass();
//
//        $this->assertTrue($model->canSetProperty('name'));
//        $this->assertTrue($model->canGetProperty('name'));
//        $this->assertFalse($model->canSetProperty('unExistingColumn'));
//        $this->assertFalse(isset($model->name));
//
//        $model->name = 'foo';
//        $this->assertTrue(isset($model->name));
//        unset($model->name);
//        $this->assertNull($model->name);
//
//        // @see https://github.com/yiisoft/yii2-gii/issues/190
//        $baseModel = new $customerClass();
//        $this->assertFalse($baseModel->hasProperty('unExistingColumn'));
//
//
//        /* @var $customer ActiveRecord */
//        $customer = new $customerClass();
//        $this->assertInstanceOf($customerClass, $customer);
//
//        $this->assertTrue($customer->canGetProperty('id'));
//        $this->assertTrue($customer->canSetProperty('id'));
//
//        // tests that we really can get and set this property
//        $this->assertNull($customer->id);
//        $customer->id = 10;
//        $this->assertNotNull($customer->id);
//
//        // Let's test relations
//        $this->assertTrue($customer->canGetProperty('orderItems'));
//        $this->assertFalse($customer->canSetProperty('orderItems'));
//
//        // Newly created model must have empty relation
//        $this->assertSame([], $customer->orderItems);
//
//        // does it still work after accessing the relation?
//        $this->assertTrue($customer->canGetProperty('orderItems'));
//        $this->assertFalse($customer->canSetProperty('orderItems'));
//
//        try {
//
//            /* @var $itemClass \yii\db\ActiveRecord */
//            $itemClass = $this->getItemClass();
//            $customer->orderItems = [new $itemClass()];
//            $this->fail('setter call above MUST throw Exception');
//
//        } catch (\Exception $e) {
//            // catch exception "Setting read-only property"
//            $this->assertInstanceOf('yii\base\InvalidCallException', $e);
//        }
//
//        // related attribute $customer->orderItems didn't change cause it's read-only
//        $this->assertSame([], $customer->orderItems);
//
//        $this->assertFalse($customer->canGetProperty('non_existing_property'));
//        $this->assertFalse($customer->canSetProperty('non_existing_property'));
//    }
}








