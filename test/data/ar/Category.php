<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace test\data\ar;

/**
 * Class Category.
 *
 * @property int $id
 * @property string $name
 */
class Category extends ActiveRecord
{
    public static function tableName()
    {
        return 'category';
    }

    public function getItems()
    {
        return $this->hasMany(Item::className(), array('category_id' => 'id'));
    }

    public function getLimitedItems()
    {
        return $this->hasMany(Item::className(), array('category_id' => 'id'))
            ->onCondition(array('item.id' => array(1, 2, 3)));
    }

    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::className(), array('item_id' => 'id'))->via('items');
    }

    public function getOrders()
    {
        return $this->hasMany(Order::className(), array('id' => 'order_id'))->via('orderItems');
    }
}


