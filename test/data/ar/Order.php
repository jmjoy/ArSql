<?php

namespace test\data\ar;

/**
 * Class Order
 *
 * @property int $id
 * @property int $customer_id
 * @property int $created_at
 * @property string $total
 */
class Order extends ActiveRecord
{
    public static $tableName;

    public static function tableName()
    {
        return static::$tableName ?: 'order';
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), array('id' => 'customer_id'));
    }

    public function getCustomerJoinedWithProfile()
    {
        return $this->hasOne(Customer::className(), array('id' => 'customer_id'))
            ->joinWith('profile');
    }

    public function getCustomerJoinedWithProfileIndexOrdered()
    {
        return $this->hasMany(Customer::className(), array('id' => 'customer_id'))
            ->joinWith('profile')->orderBy(array('profile.description' => SORT_ASC))->indexBy('name');
    }

    public function getCustomer2()
    {
        return $this->hasOne(Customer::className(), array('id' => 'customer_id'))->inverseOf('orders2');
    }

    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::className(), array('order_id' => 'id'));
    }

    public function getOrderItems2()
    {
        return $this->hasMany(OrderItem::className(), array('order_id' => 'id'))
            ->indexBy('item_id');
    }

    public function getOrderItems3()
    {
        return $this->hasMany(OrderItem::className(), array('order_id' => 'id'))
            ->indexBy(function ($row) {
                return $row['order_id'] . '_' . $row['item_id'];
            });
    }

    public function getOrderItemsWithNullFK()
    {
        return $this->hasMany(OrderItemWithNullFK::className(), array('order_id' => 'id'));
    }

    public function getItems()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->via('orderItems', function ($q) {
                // additional query configuration
            })->orderBy('item.id');
    }

    public function getItemsIndexed()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->via('orderItems')->indexBy('id');
    }

    public function getItemsWithNullFK()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->viaTable('order_item_with_null_fk', array('order_id' => 'id'));
    }

    public function getItemsInOrder1()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->via('orderItems', function ($q) {
                $q->orderBy(array('subtotal' => SORT_ASC));
            })->orderBy('name');
    }

    public function getItemsInOrder2()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->via('orderItems', function ($q) {
                $q->orderBy(array('subtotal' => SORT_DESC));
            })->orderBy('name');
    }

    public function getBooks()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->via('orderItems')
            ->where(array('category_id' => 1));
    }

    public function getBooksWithNullFK()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->via('orderItemsWithNullFK')
            ->where(array('category_id' => 1));
    }

    public function getBooksViaTable()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->viaTable('order_item', array('order_id' => 'id'))
            ->where(array('category_id' => 1));
    }

    public function getBooksWithNullFKViaTable()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->viaTable('order_item_with_null_fk', array('order_id' => 'id'))
            ->where(array('category_id' => 1));
    }

    public function getBooks2()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->onCondition(array('category_id' => 1))
            ->viaTable('order_item', array('order_id' => 'id'));
    }

    public function getBooksExplicit()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->onCondition(array('category_id' => 1))
            ->viaTable('order_item', array('order_id' => 'id'));
    }

//    public function getBooksQuerysyntax()
//    {
//        return $this->hasMany(Item::className(), ['id' => 'item_id'])
//            ->onCondition(['{{@item}}.category_id' => 1])
//            ->viaTable('order_item', ['order_id' => 'id']);
//    }

    public function getBooksExplicitA()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))->alias('bo')
            ->onCondition(array('bo.category_id' => 1))
            ->viaTable('order_item', array('order_id' => 'id'));
    }

//    public function getBooksQuerysyntaxA()
//    {
//        return $this->hasMany(Item::className(), ['id' => 'item_id'])->alias('bo')
//            ->onCondition(['{{@item}}.category_id' => 1])
//            ->viaTable('order_item', ['order_id' => 'id']);
//    }

    public function getBookItems()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))->alias('books')
            ->onCondition(array('books.category_id' => 1))
            ->viaTable('order_item', array('order_id' => 'id'));
    }

    public function getMovieItems()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))->alias('movies')
            ->onCondition(array('movies.category_id' => 2))
            ->viaTable('order_item', array('order_id' => 'id'));
    }

    public function getLimitedItems()
    {
        return $this->hasMany(Item::className(), array('id' => 'item_id'))
            ->onCondition(array('item.id' => array(3, 5)))
            ->via('orderItems');
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->created_at = time();

            return true;
        } else {
            return false;
        }
    }

    public function attributeLabels()
    {
        return array(
            'customer_id' => 'Customer',
            'total' => 'Invoice Total',
        );
    }

    public function activeAttributes()
    {
        return array(
            0 => 'customer_id'
        );
    }
}


