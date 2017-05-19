<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace arSql;

use arSql\contract\ISqlHandler;
use arSql\exception\InvalidParamException;

/**
 * ActiveQuery represents a DB query associated with an Active Record class.
 *
 * An ActiveQuery can be a normal query or be used in a relational context.
 *
 * ActiveQuery instances are usually created by [[ActiveRecord::find()]] and [[ActiveRecord::findBySql()]].
 * Relational queries are created by [[ActiveRecord::hasOne()]] and [[ActiveRecord::hasMany()]].
 *
 * Normal Query
 * ------------
 *
 * ActiveQuery mainly provides the following methods to retrieve the query results:
 *
 * - [[one()]]: returns a single record populated with the first row of data.
 * - [[all()]]: returns all records based on the query results.
 * - [[count()]]: returns the number of records.
 * - [[sum()]]: returns the sum over the specified column.
 * - [[average()]]: returns the average over the specified column.
 * - [[min()]]: returns the min over the specified column.
 * - [[max()]]: returns the max over the specified column.
 * - [[scalar()]]: returns the value of the first column in the first row of the query result.
 * - [[column()]]: returns the value of the first column in the query result.
 * - [[exists()]]: returns a value indicating whether the query result has data or not.
 *
 * Because ActiveQuery extends from [[Query]], one can use query methods, such as [[where()]],
 * [[orderBy()]] to customize the query options.
 *
 * ActiveQuery also provides the following additional query options:
 *
 * - [[with()]]: list of relations that this query should be performed with.
 * - [[joinWith()]]: reuse a relation query definition to add a join to a query.
 * - [[indexBy()]]: the name of the column by which the query result should be indexed.
 * - [[asArray()]]: whether to return each record as an array.
 *
 * These options can be configured using methods of the same name. For example:
 *
 * ```php
 * $customers = Customer::find()->with('orders')->asArray()->all();
 * ```
 *
 * Relational query
 * ----------------
 *
 * In relational context ActiveQuery represents a relation between two Active Record classes.
 *
 * Relational ActiveQuery instances are usually created by calling [[ActiveRecord::hasOne()]] and
 * [[ActiveRecord::hasMany()]]. An Active Record class declares a relation by defining
 * a getter method which calls one of the above methods and returns the created ActiveQuery object.
 *
 * A relation is specified by [[link]] which represents the association between columns
 * of different tables; and the multiplicity of the relation is indicated by [[multiple]].
 *
 * If a relation involves a junction table, it may be specified by [[via()]] or [[viaTable()]] method.
 * These methods may only be called in a relational context. Same is true for [[inverseOf()]], which
 * marks a relation as inverse of another relation and [[onCondition()]] which adds a condition that
 * is to be added to relational query join condition.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class ActiveQuery extends Query
{

    /**
     * @event Event an event that is triggered when the query is initialized via [[init()]].
     */
    const EVENT_INIT = 'init';

    /**
     * @var string the SQL statement to be executed for retrieving AR records.
     * This is set by [[ActiveRecord::findBySql()]].
     */
    public $sql;
    /**
     * @var string|array the join condition to be used when this query is used in a relational context.
     * The condition will be used in the ON part when [[ActiveQuery::joinWith()]] is called.
     * Otherwise, the condition will be used in the WHERE part of a query.
     * Please refer to [[Query::where()]] on how to specify this parameter.
     * @see onCondition()
     */
    public $on;
    /**
     * @var array a list of relations that this query should be joined with
     */
    public $joinWith;

    /**
     * @var string the name of the ActiveRecord class.
     */
    public $modelClass;
    /**
     * @var array a list of relations that this query should be performed with
     */
    public $with;
    /**
     * @var bool whether to return each record as an array. If false (default), an object
     * of [[modelClass]] will be created to represent each record.
     */
    public $asArray;
    /**
     * @var bool whether this query represents a relation to more than one record.
     * This property is only used in relational context. If true, this relation will
     * populate all query results into AR instances using [[Query::all()|all()]].
     * If false, only the first row of the results will be retrieved using [[Query::one()|one()]].
     */
    public $multiple;
    /**
     * @var ActiveRecord the primary model of a relational query.
     * This is used only in lazy loading with dynamic query options.
     */
    public $primaryModel;
    /**
     * @var array the columns of the primary and foreign tables that establish a relation.
     * The array keys must be columns of the table for this relation, and the array values
     * must be the corresponding columns from the primary table.
     * Do not prefix or quote the column names as this will be done automatically by Yii.
     * This property is only used in relational context.
     */
    public $link;
    /**
     * @var array|object the query associated with the junction table. Please call [[via()]]
     * to set this property instead of directly setting it.
     * This property is only used in relational context.
     * @see via()
     */
    public $via;
    /**
     * @var string the name of the relation that is the inverse of this relation.
     * For example, an order has a customer, which means the inverse of the "customer" relation
     * is the "orders", and the inverse of the "orders" relation is the "customer".
     * If this property is set, the primary record(s) will be referenced through the specified relation.
     * For example, `$customer->orders[0]->customer` and `$customer` will be the same object,
     * and accessing the customer of an order will not trigger new DB query.
     * This property is only used in relational context.
     * @see inverseOf()
     */
    public $inverseOf;

    /**
     * Constructor.
     * @param string $modelClass the model class associated with this query
     * @param array $config configurations to be applied to the newly created query object
     */
    public function __construct($modelClass, $config = array())
    {
        $this->modelClass = $modelClass;
        foreach ($config as $name => $value) {
            $this->$name = $value;
        }
        $this->init();
    }

    /**
     * Initializes the object.
     * This method is called at the end of the constructor. The default implementation will trigger
     * an [[EVENT_INIT]] event. If you override this method, make sure you call the parent implementation at the end
     * to ensure triggering of the event.
     */
    public function init() {
    }

    /**
     * Executes query and returns all results as an array.
     * @param Connection $db the DB connection used to create the DB command.
     * If null, the DB connection returned by [[modelClass]] will be used.
     * @return array|ActiveRecord[] the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all(ISqlHandler $sqlHandler = null)
    {
        return parent::all($sqlHandler);
    }

    /**
     * @inheritdoc
     */
    public function prepare($builder)
    {
        // NOTE: because the same ActiveQuery may be used to build different SQL statements
        // (e.g. by ActiveDataProvider, one for count query, the other for row data query,
        // it is important to make sure the same ActiveQuery can be used to build SQL statements
        // multiple times.
        if (!empty($this->joinWith)) {
            $this->buildJoinWith();
            $this->joinWith = null;    // clean it up to avoid issue https://github.com/yiisoft/yii2/issues/2687
        }

        if (empty($this->from)) {
            $this->from = array($this->getPrimaryTableName());
        }

        if (empty($this->select) && !empty($this->join)) {
            list(, $alias) = $this->getTableNameAndAlias();
            $this->select = array("$alias.*");
        }

        if ($this->primaryModel === null) {
            // eager loading
            $query = Query::create($this);
        } else {
            // lazy loading of a relation
            $where = $this->where;

            if ($this->via instanceof self) {
                // via junction table
                $viaModels = $this->via->findJunctionRows(array($this->primaryModel));
                $this->filterByModels($viaModels);
            } elseif (is_array($this->via)) {
                // via relation
                /* @var $viaQuery ActiveQuery */
                list($viaName, $viaQuery) = $this->via;
                if ($viaQuery->multiple) {
                    $viaModels = $viaQuery->all();
                    $this->primaryModel->populateRelation($viaName, $viaModels);
                } else {
                    $model = $viaQuery->one();
                    $this->primaryModel->populateRelation($viaName, $model);
                    $viaModels = $model === null ? array() : array($model);
                }
                $this->filterByModels($viaModels);
            } else {
                $this->filterByModels(array($this->primaryModel));
            }

            $query = Query::create($this);
            $this->where = $where;
        }

        if (!empty($this->on)) {
            $query->andWhere($this->on);
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function populate($rows)
    {
        if (empty($rows)) {
            return array();
        }

        $models = $this->createModels($rows);
        if (!empty($this->join) && $this->indexBy === null) {
            $models = $this->removeDuplicatedModels($models);
        }
        if (!empty($this->with)) {
            $this->findWith($this->with, $models);
        }

        if ($this->inverseOf !== null) {
            $this->addInverseRelations($models);
        }

        if (!$this->asArray) {
            foreach ($models as $model) {
                $model->afterFind();
            }
        }

        return $models;
    }

    /**
     * Removes duplicated models by checking their primary key values.
     * This method is mainly called when a join query is performed, which may cause duplicated rows being returned.
     * @param array $models the models to be checked
     * @throws InvalidConfigException if model primary key is empty
     * @return array the distinctive models
     */
    private function removeDuplicatedModels($models)
    {
        $hash = array();
        /* @var $class ActiveRecord */
        $class = $this->modelClass;
        $pks = $class::primaryKey();

        if (count($pks) > 1) {
            // composite primary key
            foreach ($models as $i => $model) {
                $key = array();
                foreach ($pks as $pk) {
                    if (!isset($model[$pk])) {
                        // do not continue if the primary key is not part of the result set
                        break 2;
                    }
                    $key[] = $model[$pk];
                }
                $key = serialize($key);
                if (isset($hash[$key])) {
                    unset($models[$i]);
                } else {
                    $hash[$key] = true;
                }
            }
        } elseif (empty($pks)) {
            throw new InvalidConfigException("Primary key of '{$class}' can not be empty.");
        } else {
            // single column primary key
            $pk = reset($pks);
            foreach ($models as $i => $model) {
                if (!isset($model[$pk])) {
                    // do not continue if the primary key is not part of the result set
                    break;
                }
                $key = $model[$pk];
                if (isset($hash[$key])) {
                    unset($models[$i]);
                } elseif ($key !== null) {
                    $hash[$key] = true;
                }
            }
        }

        return array_values($models);
    }

    /**
     * Executes query and returns a single row of result.
     * @param Connection|null $db the DB connection used to create the DB command.
     * If `null`, the DB connection returned by [[modelClass]] will be used.
     * @return ActiveRecord|array|null a single row of query result. Depending on the setting of [[asArray]],
     * the query result may be either an array or an ActiveRecord object. `null` will be returned
     * if the query results in nothing.
     */
    public function one(ISqlHandler $sqlHandler = null)
    {
        $row = parent::one($sqlHandler);
        if ($row !== false) {
            $models = $this->populate(array($row));
            return reset($models) ?: null;
        } else {
            return null;
        }
    }

    /**
     * Creates a DB command that can be used to execute this query.
     * @param Connection|null $db the DB connection used to create the DB command.
     * If `null`, the DB connection returned by [[modelClass]] will be used.
     * @return Command the created DB command instance.
     */
    public function createCommand(ISqlHandler $sqlHandler = null)
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        if ($this->sql === null) {
            $command = new Command($sqlHandler);
            list ($sql, $params) = $command->getBuilder()->build($this);
        } else {
            $sql = $this->sql;
            $params = $this->params;
        }

        return new Command($sqlHandler, $sql, $params);
    }

    /**
     * @inheritdoc
     */
    protected function queryScalar($selectExpression, ISqlHandler $sqlHandler = null)
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;

        if ($this->sql === null) {
            return parent::queryScalar($selectExpression, $sqlHandler);
        }

        return Query::instantiate()->select(array($selectExpression))
            ->from(array('c' => "({$this->sql})"))
            ->params($this->params)
            ->createCommand($sqlHandler)
            ->queryScalar();
    }

    /**
     * Joins with the specified relations.
     *
     * This method allows you to reuse existing relation definitions to perform JOIN queries.
     * Based on the definition of the specified relation(s), the method will append one or multiple
     * JOIN statements to the current query.
     *
     * If the `$eagerLoading` parameter is true, the method will also perform eager loading for the specified relations,
     * which is equivalent to calling [[with()]] using the specified relations.
     *
     * Note that because a JOIN query will be performed, you are responsible to disambiguate column names.
     *
     * This method differs from [[with()]] in that it will build up and execute a JOIN SQL statement
     * for the primary table. And when `$eagerLoading` is true, it will call [[with()]] in addition with the specified relations.
     *
     * @param string|array $with the relations to be joined. This can either be a string, representing a relation name or
     * an array with the following semantics:
     *
     * - Each array element represents a single relation.
     * - You may specify the relation name as the array key and provide an anonymous functions that
     *   can be used to modify the relation queries on-the-fly as the array value.
     * - If a relation query does not need modification, you may use the relation name as the array value.
     *
     * The relation name may optionally contain an alias for the relation table (e.g. `books b`).
     *
     * Sub-relations can also be specified, see [[with()]] for the syntax.
     *
     * In the following you find some examples:
     *
     * ```php
     * // find all orders that contain books, and eager loading "books"
     * Order::find()->joinWith('books', true, 'INNER JOIN')->all();
     * // find all orders, eager loading "books", and sort the orders and books by the book names.
     * Order::find()->joinWith([
     *     'books' => function (\yii\db\ActiveQuery $query) {
     *         $query->orderBy('item.name');
     *     }
     * ])->all();
     * // find all orders that contain books of the category 'Science fiction', using the alias "b" for the books table
     * Order::find()->joinWith(['books b'], true, 'INNER JOIN')->where(['b.category' => 'Science fiction'])->all();
     * ```
     *
     * The alias syntax is available since version 2.0.7.
     *
     * @param bool|array $eagerLoading whether to eager load the relations specified in `$with`.
     * When this is a boolean, it applies to all relations specified in `$with`. Use an array
     * to explicitly list which relations in `$with` need to be eagerly loaded. Defaults to `true`.
     * @param string|array $joinType the join type of the relations specified in `$with`.
     * When this is a string, it applies to all relations specified in `$with`. Use an array
     * in the format of `relationName => joinType` to specify different join types for different relations.
     * @return $this the query object itself
     */
    public function joinWith($with, $eagerLoading = true, $joinType = 'LEFT JOIN')
    {
        $relations = array();
        foreach ((array) $with as $name => $callback) {
            if (is_int($name)) {
                $name = $callback;
                $callback = null;
            }

            if (preg_match('/^(.*?)(?:\s+AS\s+|\s+)(\w+)$/i', $name, $matches)) {
                // relation is defined with an alias, adjust callback to apply alias
                list(, $relation, $alias) = $matches;
                $name = $relation;
                $callback = function ($query) use ($callback, $alias) {
                    /** @var $query ActiveQuery */
                    $query->alias($alias);
                    if ($callback !== null) {
                        call_user_func($callback, $query);
                    }
                };
            }

            if ($callback === null) {
                $relations[] = $name;
            } else {
                $relations[$name] = $callback;
            }
        }
        $this->joinWith[] = array($relations, $eagerLoading, $joinType);
        return $this;
    }

    private function buildJoinWith()
    {
        $join = $this->join;
        $this->join = array();

        $model = new $this->modelClass;
        foreach ($this->joinWith as $config) {
            list ($with, $eagerLoading, $joinType) = $config;
            $this->joinWithRelations($model, $with, $joinType);

            if (is_array($eagerLoading)) {
                foreach ($with as $name => $callback) {
                    if (is_int($name)) {
                        if (!in_array($callback, $eagerLoading, true)) {
                            unset($with[$name]);
                        }
                    } elseif (!in_array($name, $eagerLoading, true)) {
                        unset($with[$name]);
                    }
                }
            } elseif (!$eagerLoading) {
                $with = array();
            }

            $this->with($with);
        }

        // remove duplicated joins added by joinWithRelations that may be added
        // e.g. when joining a relation and a via relation at the same time
        $uniqueJoins = array();
        foreach ($this->join as $j) {
            $uniqueJoins[serialize($j)] = $j;
        }
        $this->join = array_values($uniqueJoins);

        if (!empty($join)) {
            // append explicit join to joinWith()
            // https://github.com/yiisoft/yii2/issues/2880
            $this->join = empty($this->join) ? $join : array_merge($this->join, $join);
        }
    }

    /**
     * Inner joins with the specified relations.
     * This is a shortcut method to [[joinWith()]] with the join type set as "INNER JOIN".
     * Please refer to [[joinWith()]] for detailed usage of this method.
     * @param string|array $with the relations to be joined with.
     * @param bool|array $eagerLoading whether to eager loading the relations.
     * @return $this the query object itself
     * @see joinWith()
     */
    public function innerJoinWith($with, $eagerLoading = true)
    {
        return $this->joinWith($with, $eagerLoading, 'INNER JOIN');
    }

    /**
     * Modifies the current query by adding join fragments based on the given relations.
     * @param ActiveRecord $model the primary model
     * @param array $with the relations to be joined
     * @param string|array $joinType the join type
     */
    private function joinWithRelations($model, $with, $joinType)
    {
        $relations = array();

        foreach ($with as $name => $callback) {
            if (is_int($name)) {
                $name = $callback;
                $callback = null;
            }

            $primaryModel = $model;
            $parent = $this;
            $prefix = '';
            while (($pos = strpos($name, '.')) !== false) {
                $childName = substr($name, $pos + 1);
                $name = substr($name, 0, $pos);
                $fullName = $prefix === '' ? $name : "$prefix.$name";
                if (!isset($relations[$fullName])) {
                    $relations[$fullName] = $relation = $primaryModel->getRelation($name);
                    $this->joinWithRelation($parent, $relation, $this->getJoinType($joinType, $fullName));
                } else {
                    $relation = $relations[$fullName];
                }
                $primaryModel = new $relation->modelClass;
                $parent = $relation;
                $prefix = $fullName;
                $name = $childName;
            }

            $fullName = $prefix === '' ? $name : "$prefix.$name";
            if (!isset($relations[$fullName])) {
                $relations[$fullName] = $relation = $primaryModel->getRelation($name);
                if ($callback !== null) {
                    call_user_func($callback, $relation);
                }
                if (!empty($relation->joinWith)) {
                    $relation->buildJoinWith();
                }
                $this->joinWithRelation($parent, $relation, $this->getJoinType($joinType, $fullName));
            }
        }
    }

    /**
     * Returns the join type based on the given join type parameter and the relation name.
     * @param string|array $joinType the given join type(s)
     * @param string $name relation name
     * @return string the real join type
     */
    private function getJoinType($joinType, $name)
    {
        if (is_array($joinType) && isset($joinType[$name])) {
            return $joinType[$name];
        } else {
            return is_string($joinType) ? $joinType : 'INNER JOIN';
        }
    }

    /**
     * Returns the table name and the table alias for [[modelClass]].
     * @return array the table name and the table alias.
     * @internal
     */
    private function getTableNameAndAlias()
    {
        if (empty($this->from)) {
            $tableName = $this->getPrimaryTableName();
        } else {
            $tableName = '';
            foreach ($this->from as $alias => $tableName) {
                if (is_string($alias)) {
                    return array($tableName, $alias);
                } else {
                    break;
                }
            }
        }

        if (preg_match('/^(.*?)\s+({{\w+}}|\w+)$/', $tableName, $matches)) {
            $alias = $matches[2];
        } else {
            $alias = $tableName;
        }

        return array($tableName, $alias);
    }

    /**
     * Joins a parent query with a child query.
     * The current query object will be modified accordingly.
     * @param ActiveQuery $parent
     * @param ActiveQuery $child
     * @param string $joinType
     */
    private function joinWithRelation($parent, $child, $joinType)
    {
        $via = $child->via;
        $child->via = null;
        if ($via instanceof ActiveQuery) {
            // via table
            $this->joinWithRelation($parent, $via, $joinType);
            $this->joinWithRelation($via, $child, $joinType);
            return;
        } elseif (is_array($via)) {
            // via relation
            $this->joinWithRelation($parent, $via[1], $joinType);
            $this->joinWithRelation($via[1], $child, $joinType);
            return;
        }

        list ($parentTable, $parentAlias) = $parent->getTableNameAndAlias();
        list ($childTable, $childAlias) = $child->getTableNameAndAlias();

        if (!empty($child->link)) {

            // if (strpos($parentAlias, '{{') === false) {
            //     $parentAlias = '{{' . $parentAlias . '}}';
            // }
            // if (strpos($childAlias, '{{') === false) {
            //     $childAlias = '{{' . $childAlias . '}}';
            // }

            $on = array();
            foreach ($child->link as $childColumn => $parentColumn) {
                // $on[] = "$parentAlias.[[$parentColumn]] = $childAlias.[[$childColumn]]";
                $on[] = "$parentAlias.$parentColumn = $childAlias.$childColumn";
            }
            $on = implode(' AND ', $on);
            if (!empty($child->on)) {
                $on = array('and', $on, $child->on);
            }
        } else {
            $on = $child->on;
        }
        $this->join($joinType, empty($child->from) ? $childTable : $child->from, $on);

        if (!empty($child->where)) {
            $this->andWhere($child->where);
        }
        if (!empty($child->having)) {
            $this->andHaving($child->having);
        }
        if (!empty($child->orderBy)) {
            $this->addOrderBy($child->orderBy);
        }
        if (!empty($child->groupBy)) {
            $this->addGroupBy($child->groupBy);
        }
        if (!empty($child->params)) {
            $this->addParams($child->params);
        }
        if (!empty($child->join)) {
            foreach ($child->join as $join) {
                $this->join[] = $join;
            }
        }
        if (!empty($child->union)) {
            foreach ($child->union as $union) {
                $this->union[] = $union;
            }
        }
    }

    /**
     * Sets the ON condition for a relational query.
     * The condition will be used in the ON part when [[ActiveQuery::joinWith()]] is called.
     * Otherwise, the condition will be used in the WHERE part of a query.
     *
     * Use this method to specify additional conditions when declaring a relation in the [[ActiveRecord]] class:
     *
     * ```php
     * public function getActiveUsers()
     * {
     *     return $this->hasMany(User::className(), ['id' => 'user_id'])
     *                 ->onCondition(['active' => true]);
     * }
     * ```
     *
     * Note that this condition is applied in case of a join as well as when fetching the related records.
     * Thus only fields of the related table can be used in the condition. Trying to access fields of the primary
     * record will cause an error in a non-join-query.
     *
     * @param string|array $condition the ON condition. Please refer to [[Query::where()]] on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return $this the query object itself
     */
    public function onCondition($condition, $params = array())
    {
        $this->on = $condition;
        $this->addParams($params);
        return $this;
    }

    /**
     * Adds an additional ON condition to the existing one.
     * The new condition and the existing one will be joined using the 'AND' operator.
     * @param string|array $condition the new ON condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return $this the query object itself
     * @see onCondition()
     * @see orOnCondition()
     */
    public function andOnCondition($condition, $params = array())
    {
        if ($this->on === null) {
            $this->on = $condition;
        } else {
            $this->on = array('and', $this->on, $condition);
        }
        $this->addParams($params);
        return $this;
    }

    /**
     * Adds an additional ON condition to the existing one.
     * The new condition and the existing one will be joined using the 'OR' operator.
     * @param string|array $condition the new ON condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return $this the query object itself
     * @see onCondition()
     * @see andOnCondition()
     */
    public function orOnCondition($condition, $params = array())
    {
        if ($this->on === null) {
            $this->on = $condition;
        } else {
            $this->on = array('or', $this->on, $condition);
        }
        $this->addParams($params);
        return $this;
    }

    /**
     * Specifies the junction table for a relational query.
     *
     * Use this method to specify a junction table when declaring a relation in the [[ActiveRecord]] class:
     *
     * ```php
     * public function getItems()
     * {
     *     return $this->hasMany(Item::className(), ['id' => 'item_id'])
     *                 ->viaTable('order_item', ['order_id' => 'id']);
     * }
     * ```
     *
     * @param string $tableName the name of the junction table.
     * @param array $link the link between the junction table and the table associated with [[primaryModel]].
     * The keys of the array represent the columns in the junction table, and the values represent the columns
     * in the [[primaryModel]] table.
     * @param callable $callable a PHP callback for customizing the relation associated with the junction table.
     * Its signature should be `function($query)`, where `$query` is the query to be customized.
     * @return $this the query object itself
     * @see via()
     */
    public function viaTable($tableName, $link, $callable = null)
    {
        $relation = new ActiveQuery(get_class($this->primaryModel), array(
            'from' => array($tableName),
            'link' => $link,
            'multiple' => true,
            'asArray' => true,
        ));
        $this->via = $relation;
        if ($callable !== null) {
            call_user_func($callable, $relation);
        }

        return $this;
    }

    /**
     * Define an alias for the table defined in [[modelClass]].
     *
     * This method will adjust [[from]] so that an already defined alias will be overwritten.
     * If none was defined, [[from]] will be populated with the given alias.
     *
     * @param string $alias the table alias.
     * @return $this the query object itself
     * @since 2.0.7
     */
    public function alias($alias)
    {
        if (empty($this->from) || count($this->from) < 2) {
            list($tableName, ) = $this->getTableNameAndAlias();
            $this->from = array($alias => $tableName);
        } else {
            $tableName = $this->getPrimaryTableName();

            foreach ($this->from as $key => $table) {
                if ($table === $tableName) {
                    unset($this->from[$key]);
                    $this->from[$alias] = $tableName;
                }
            }
        }
        return $this;
    }

    /**
     * Returns table names used in [[from]] indexed by aliases.
     * Both aliases and names are enclosed into {{ and }}.
     * @return string[] table names indexed by aliases
     * @throws \yii\base\InvalidConfigException
     * @since 2.0.12
     */
    public function getTablesUsedInFrom()
    {
        if (empty($this->from)) {
            $tableNames = array($this->getPrimaryTableName());
        } elseif (is_array($this->from)) {
            $tableNames = $this->from;
        } elseif (is_string($this->from)) {
            $tableNames = preg_split('/\s*,\s*/', trim($this->from), -1, PREG_SPLIT_NO_EMPTY);
        } else {
            throw new InvalidConfigException(gettype($this->from) . ' in $from is not supported.');
        }

        // Clean up table names and aliases
        $cleanedUpTableNames = array();
        foreach ($tableNames as $alias => $tableName) {
            if (!is_string($alias)) {
                $pattern = <<<PATTERN
~
^
\s*
(
    (?:['"`\[]|{{)
    .*?
    (?:['"`\]]|}})
    |
    \w+
)
(?:
    (?:
        \s+
        (?:as)?
        \s*
    )
    (
       (?:['"`\[]|{{)
        .*?
        (?:['"`\]]|}})
        |
        \w+
    )
)?
\s*
$
~iux
PATTERN;
                if (preg_match($pattern, $tableName, $matches)) {
                    if (isset($matches[1])) {
                        if (isset($matches[2])) {
                            list(, $tableName, $alias) = $matches;
                        } else {
                            $tableName = $alias = $matches[1];
                        }
                        if (strncmp($alias, '{{', 2) !== 0) {
                            $alias = '{{' . $alias . '}}';
                        }
                        if (strncmp($tableName, '{{', 2) !== 0) {
                            $tableName = '{{' . $tableName . '}}';
                        }
                    }
                }
            }

            $tableName = str_replace(array("'", '"', '`', '[', ']'), '', $tableName);
            $alias = str_replace(array("'", '"', '`', '[', ']'), '', $alias);

            $cleanedUpTableNames[$alias] = $tableName;
        }

        return $cleanedUpTableNames;
    }

    /**
     * @return string primary table name
     * @since 2.0.12
     */
    protected function getPrimaryTableName()
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        return $modelClass::tableName();
    }
    /**
     * Sets the [[asArray]] property.
     * @param bool $value whether to return the query results in terms of arrays instead of Active Records.
     * @return $this the query object itself
     */
    public function asArray($value = true)
    {
        $this->asArray = $value;
        return $this;
    }

    /**
     * Specifies the relations with which this query should be performed.
     *
     * The parameters to this method can be either one or multiple strings, or a single array
     * of relation names and the optional callbacks to customize the relations.
     *
     * A relation name can refer to a relation defined in [[modelClass]]
     * or a sub-relation that stands for a relation of a related record.
     * For example, `orders.address` means the `address` relation defined
     * in the model class corresponding to the `orders` relation.
     *
     * The following are some usage examples:
     *
     * ```php
     * // find customers together with their orders and country
     * Customer::find()->with('orders', 'country')->all();
     * // find customers together with their orders and the orders' shipping address
     * Customer::find()->with('orders.address')->all();
     * // find customers together with their country and orders of status 1
     * Customer::find()->with([
     *     'orders' => function (\yii\db\ActiveQuery $query) {
     *         $query->andWhere('status = 1');
     *     },
     *     'country',
     * ])->all();
     * ```
     *
     * You can call `with()` multiple times. Each call will add relations to the existing ones.
     * For example, the following two statements are equivalent:
     *
     * ```php
     * Customer::find()->with('orders', 'country')->all();
     * Customer::find()->with('orders')->with('country')->all();
     * ```
     *
     * @return $this the query object itself
     */
    public function with()
    {
        $with = func_get_args();
        if (isset($with[0]) && is_array($with[0])) {
            // the parameter is given as an array
            $with = $with[0];
        }

        if (empty($this->with)) {
            $this->with = $with;
        } elseif (!empty($with)) {
            foreach ($with as $name => $value) {
                if (is_int($name)) {
                    // repeating relation is fine as normalizeRelations() handle it well
                    $this->with[] = $value;
                } else {
                    $this->with[$name] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Converts found rows into model instances
     * @param array $rows
     * @return array|ActiveRecord[]
     * @since 2.0.11
     */
    protected function createModels($rows)
    {
        $models = array();
        if ($this->asArray) {
            if ($this->indexBy === null) {
                return $rows;
            }
            foreach ($rows as $row) {
                if (is_string($this->indexBy)) {
                    $key = $row[$this->indexBy];
                } else {
                    $key = call_user_func($this->indexBy, $row);
                }
                $models[$key] = $row;
            }
        } else {
            /* @var $class ActiveRecord */
            $class = $this->modelClass;
            if ($this->indexBy === null) {
                foreach ($rows as $row) {
                    $model = $class::instantiate($row);
                    $modelClass = get_class($model);
                    $modelClass::populateRecord($model, $row);
                    $models[] = $model;
                }
            } else {
                foreach ($rows as $row) {
                    $model = $class::instantiate($row);
                    $modelClass = get_class($model);
                    $modelClass::populateRecord($model, $row);
                    if (is_string($this->indexBy)) {
                        $key = $model->{$this->indexBy};
                    } else {
                        $key = call_user_func($this->indexBy, $model);
                    }
                    $models[$key] = $model;
                }
            }
        }

        return $models;
    }

    /**
     * Finds records corresponding to one or multiple relations and populates them into the primary models.
     * @param array $with a list of relations that this query should be performed with. Please
     * refer to [[with()]] for details about specifying this parameter.
     * @param array|ActiveRecord[] $models the primary models (can be either AR instances or arrays)
     */
    public function findWith($with, &$models)
    {
        $primaryModel = reset($models);
        if (!$primaryModel instanceof ActiveRecord) {
            $primaryModel = new $this->modelClass;
        }
        $relations = $this->normalizeRelations($primaryModel, $with);
        /* @var $relation ActiveQuery */
        foreach ($relations as $name => $relation) {
            if ($relation->asArray === null) {
                // inherit asArray from primary query
                $relation->asArray($this->asArray);
            }
            $relation->populateRelation($name, $models);
        }
    }

    /**
     * @param ActiveRecord $model
     * @param array $with
     * @return ActiveQueryInterface[]
     */
    private function normalizeRelations($model, $with)
    {
        $relations = array();
        foreach ($with as $name => $callback) {
            if (is_int($name)) {
                $name = $callback;
                $callback = null;
            }
            if (($pos = strpos($name, '.')) !== false) {
                // with sub-relations
                $childName = substr($name, $pos + 1);
                $name = substr($name, 0, $pos);
            } else {
                $childName = null;
            }

            if (!isset($relations[$name])) {
                $relation = $model->getRelation($name);
                $relation->primaryModel = null;
                $relations[$name] = $relation;
            } else {
                $relation = $relations[$name];
            }

            if (isset($childName)) {
                $relation->with[$childName] = $callback;
            } elseif ($callback !== null) {
                call_user_func($callback, $relation);
            }
        }

        return $relations;
    }
    /**
     * Clones internal objects.
     */
    public function __clone()
    {
        parent::__clone();
        // make a clone of "via" object so that the same query object can be reused multiple times
        if (is_object($this->via)) {
            $this->via = clone $this->via;
        } elseif (is_array($this->via)) {
            $this->via = array($this->via[0], clone $this->via[1]);
        }
    }

    /**
     * Specifies the relation associated with the junction table.
     *
     * Use this method to specify a pivot record/table when declaring a relation in the [[ActiveRecord]] class:
     *
     * ```php
     * class Order extends ActiveRecord
     * {
     *    public function getOrderItems() {
     *        return $this->hasMany(OrderItem::className(), ['order_id' => 'id']);
     *    }
     *
     *    public function getItems() {
     *        return $this->hasMany(Item::className(), ['id' => 'item_id'])
     *                    ->via('orderItems');
     *    }
     * }
     * ```
     *
     * @param string $relationName the relation name. This refers to a relation declared in [[primaryModel]].
     * @param callable $callable a PHP callback for customizing the relation associated with the junction table.
     * Its signature should be `function($query)`, where `$query` is the query to be customized.
     * @return $this the relation object itself.
     */
    public function via($relationName, $callable = null)
    {
        $relation = $this->primaryModel->getRelation($relationName);
        $this->via = array($relationName, $relation);
        if ($callable !== null) {
            call_user_func($callable, $relation);
        }
        return $this;
    }

    /**
     * Sets the name of the relation that is the inverse of this relation.
     * For example, an order has a customer, which means the inverse of the "customer" relation
     * is the "orders", and the inverse of the "orders" relation is the "customer".
     * If this property is set, the primary record(s) will be referenced through the specified relation.
     * For example, `$customer->orders[0]->customer` and `$customer` will be the same object,
     * and accessing the customer of an order will not trigger a new DB query.
     *
     * Use this method when declaring a relation in the [[ActiveRecord]] class:
     *
     * ```php
     * public function getOrders()
     * {
     *     return $this->hasMany(Order::className(), ['customer_id' => 'id'])->inverseOf('customer');
     * }
     * ```
     *
     * @param string $relationName the name of the relation that is the inverse of this relation.
     * @return $this the relation object itself.
     */
    public function inverseOf($relationName)
    {
        $this->inverseOf = $relationName;
        return $this;
    }

    /**
     * Finds the related records for the specified primary record.
     * This method is invoked when a relation of an ActiveRecord is being accessed in a lazy fashion.
     * @param string $name the relation name
     * @param ActiveRecord|BaseActiveRecord $model the primary model
     * @return mixed the related record(s)
     * @throws InvalidParamException if the relation is invalid
     */
    public function findFor($name, $model)
    {
        if (method_exists($model, 'get' . $name)) {
            $method = new \ReflectionMethod($model, 'get' . $name);
            $realName = lcfirst(substr($method->getName(), 3));
            if ($realName !== $name) {
                throw new InvalidParamException('Relation names are case sensitive. ' . get_class($model) . " has a relation named \"$realName\" instead of \"$name\".");
            }
        }

        return $this->multiple ? $this->all() : $this->one();
    }

    /**
     * If applicable, populate the query's primary model into the related records' inverse relationship
     * @param array $result the array of related records as generated by [[populate()]]
     * @since 2.0.9
     */
    private function addInverseRelations(&$result)
    {
        if ($this->inverseOf === null) {
            return;
        }

        foreach ($result as $i => $relatedModel) {
            if ($relatedModel instanceof ActiveRecord) {
                if (!isset($inverseRelation)) {
                    $inverseRelation = $relatedModel->getRelation($this->inverseOf);
                }
                $relatedModel->populateRelation($this->inverseOf, $inverseRelation->multiple ? array($this->primaryModel) : $this->primaryModel);
            } else {
                if (!isset($inverseRelation)) {
                    $model = (new $this->modelClass);
                    $inverseRelation = $model->getRelation($this->inverseOf);
                }
                $result[$i][$this->inverseOf] = $inverseRelation->multiple ? array($this->primaryModel) : $this->primaryModel;
            }
        }
    }

    /**
     * Finds the related records and populates them into the primary models.
     * @param string $name the relation name
     * @param array $primaryModels primary models
     * @return array the related models
     * @throws InvalidConfigException if [[link]] is invalid
     */
    public function populateRelation($name, &$primaryModels)
    {
        if (!is_array($this->link)) {
            throw new InvalidConfigException('Invalid link: it must be an array of key-value pairs.');
        }

        if ($this->via instanceof self) {
            // via junction table
            /* @var $viaQuery ActiveRelationTrait */
            $viaQuery = $this->via;
            $viaModels = $viaQuery->findJunctionRows($primaryModels);
            $this->filterByModels($viaModels);
        } elseif (is_array($this->via)) {
            // via relation
            /* @var $viaQuery ActiveRelationTrait|ActiveQueryTrait */
            list($viaName, $viaQuery) = $this->via;
            if ($viaQuery->asArray === null) {
                // inherit asArray from primary query
                $viaQuery->asArray($this->asArray);
            }
            $viaQuery->primaryModel = null;
            $viaModels = $viaQuery->populateRelation($viaName, $primaryModels);
            $this->filterByModels($viaModels);
        } else {
            $this->filterByModels($primaryModels);
        }

        if (!$this->multiple && count($primaryModels) === 1) {
            $model = $this->one();
            foreach ($primaryModels as $i => $primaryModel) {
                if ($primaryModel instanceof ActiveRecord) {
                    $primaryModel->populateRelation($name, $model);
                } else {
                    $primaryModels[$i][$name] = $model;
                }
                if ($this->inverseOf !== null) {
                    $this->populateInverseRelation($primaryModels, array($model), $name, $this->inverseOf);
                }
            }

            return array($model);
        } else {
            // https://github.com/yiisoft/yii2/issues/3197
            // delay indexing related models after buckets are built
            $indexBy = $this->indexBy;
            $this->indexBy = null;
            $models = $this->all();

            if (isset($viaModels, $viaQuery)) {
                $buckets = $this->buildBuckets($models, $this->link, $viaModels, $viaQuery->link);
            } else {
                $buckets = $this->buildBuckets($models, $this->link);
            }

            $this->indexBy = $indexBy;
            if ($this->indexBy !== null && $this->multiple) {
                $buckets = $this->indexBuckets($buckets, $this->indexBy);
            }

            $link = array_values(isset($viaQuery) ? $viaQuery->link : $this->link);
            foreach ($primaryModels as $i => $primaryModel) {
                if ($this->multiple && count($link) === 1 && is_array($keys = $primaryModel[reset($link)])) {
                    $value = array();
                    foreach ($keys as $key) {
                        $key = $this->normalizeModelKey($key);
                        if (isset($buckets[$key])) {
                            if ($this->indexBy !== null) {
                                // if indexBy is set, array_merge will cause renumbering of numeric array
                                foreach ($buckets[$key] as $bucketKey => $bucketValue) {
                                    $value[$bucketKey] = $bucketValue;
                                }
                            } else {
                                $value = array_merge($value, $buckets[$key]);
                            }
                        }
                    }
                } else {
                    $key = $this->getModelKey($primaryModel, $link);
                    $value = isset($buckets[$key]) ? $buckets[$key] : ($this->multiple ? array() : null);
                }
                if ($primaryModel instanceof ActiveRecord) {
                    $primaryModel->populateRelation($name, $value);
                } else {
                    $primaryModels[$i][$name] = $value;
                }
            }
            if ($this->inverseOf !== null) {
                $this->populateInverseRelation($primaryModels, $models, $name, $this->inverseOf);
            }

            return $models;
        }
    }

    /**
     * @param ActiveRecord[] $primaryModels primary models
     * @param ActiveRecord[] $models models
     * @param string $primaryName the primary relation name
     * @param string $name the relation name
     */
    private function populateInverseRelation(&$primaryModels, $models, $primaryName, $name)
    {
        if (empty($models) || empty($primaryModels)) {
            return;
        }
        $model = reset($models);
        $refModel = (new $this->modelClass);
        /* @var $relation ActiveQueryInterface|ActiveQuery */
        $relation = $model instanceof ActiveRecord ? $model->getRelation($name) : $refModel->getRelation($name);

        if ($relation->multiple) {
            $buckets = $this->buildBuckets($primaryModels, $relation->link, null, null, false);
            if ($model instanceof ActiveRecord) {
                foreach ($models as $model) {
                    $key = $this->getModelKey($model, $relation->link);
                    $model->populateRelation($name, isset($buckets[$key]) ? $buckets[$key] : array());
                }
            } else {
                foreach ($primaryModels as $i => $primaryModel) {
                    if ($this->multiple) {
                        foreach ($primaryModel as $j => $m) {
                            $key = $this->getModelKey($m, $relation->link);
                            $primaryModels[$i][$j][$name] = isset($buckets[$key]) ? $buckets[$key] : array();
                        }
                    } elseif (!empty($primaryModel[$primaryName])) {
                        $key = $this->getModelKey($primaryModel[$primaryName], $relation->link);
                        $primaryModels[$i][$primaryName][$name] = isset($buckets[$key]) ? $buckets[$key] : array();
                    }
                }
            }
        } else {
            if ($this->multiple) {
                foreach ($primaryModels as $i => $primaryModel) {
                    foreach ($primaryModel[$primaryName] as $j => $m) {
                        if ($m instanceof ActiveRecord) {
                            $m->populateRelation($name, $primaryModel);
                        } else {
                            $primaryModels[$i][$primaryName][$j][$name] = $primaryModel;
                        }
                    }
                }
            } else {
                foreach ($primaryModels as $i => $primaryModel) {
                    if ($primaryModels[$i][$primaryName] instanceof ActiveRecord) {
                        $primaryModels[$i][$primaryName]->populateRelation($name, $primaryModel);
                    } elseif (!empty($primaryModels[$i][$primaryName])) {
                        $primaryModels[$i][$primaryName][$name] = $primaryModel;
                    }
                }
            }
        }
    }

    /**
     * @param array $models
     * @param array $link
     * @param array $viaModels
     * @param array $viaLink
     * @param bool $checkMultiple
     * @return array
     */
    private function buildBuckets($models, $link, $viaModels = null, $viaLink = null, $checkMultiple = true)
    {
        if ($viaModels !== null) {
            $map = array();
            $viaLinkKeys = array_keys($viaLink);
            $linkValues = array_values($link);
            foreach ($viaModels as $viaModel) {
                $key1 = $this->getModelKey($viaModel, $viaLinkKeys);
                $key2 = $this->getModelKey($viaModel, $linkValues);
                $map[$key2][$key1] = true;
            }
        }

        $buckets = array();
        $linkKeys = array_keys($link);

        if (isset($map)) {
            foreach ($models as $model) {
                $key = $this->getModelKey($model, $linkKeys);
                if (isset($map[$key])) {
                    foreach (array_keys($map[$key]) as $key2) {
                        $buckets[$key2][] = $model;
                    }
                }
            }
        } else {
            foreach ($models as $model) {
                $key = $this->getModelKey($model, $linkKeys);
                $buckets[$key][] = $model;
            }
        }

        if ($checkMultiple && !$this->multiple) {
            foreach ($buckets as $i => $bucket) {
                $buckets[$i] = reset($bucket);
            }
        }

        return $buckets;
    }


    /**
     * Indexes buckets by column name.
     *
     * @param array $buckets
     * @param string|callable $indexBy the name of the column by which the query results should be indexed by.
     * This can also be a callable (e.g. anonymous function) that returns the index value based on the given row data.
     * @return array
     */
    private function indexBuckets($buckets, $indexBy)
    {
        $result = array();
        foreach ($buckets as $key => $models) {
            $result[$key] = array();
            foreach ($models as $model) {
                $index = is_string($indexBy) ? $model[$indexBy] : call_user_func($indexBy, $model);
                $result[$key][$index] = $model;
            }
        }
        return $result;
    }

    /**
     * @param array $attributes the attributes to prefix
     * @return array
     */
    private function prefixKeyColumns($attributes)
    {
        if ($this instanceof ActiveQuery && (!empty($this->join) || !empty($this->joinWith))) {
            if (empty($this->from)) {
                /* @var $modelClass ActiveRecord */
                $modelClass = $this->modelClass;
                $alias = $modelClass::tableName();
            } else {
                foreach ($this->from as $alias => $table) {
                    if (!is_string($alias)) {
                        $alias = $table;
                    }
                    break;
                }
            }
            if (isset($alias)) {
                foreach ($attributes as $i => $attribute) {
                    $attributes[$i] = "$alias.$attribute";
                }
            }
        }
        return $attributes;
    }

    /**
     * @param array $models
     */
    private function filterByModels($models)
    {
        $attributes = array_keys($this->link);

        $attributes = $this->prefixKeyColumns($attributes);

        $values = array();
        if (count($attributes) === 1) {
            // single key
            $attribute = reset($this->link);
            foreach ($models as $model) {
                if (($value = $model[$attribute]) !== null) {
                    if (is_array($value)) {
                        $values = array_merge($values, $value);
                    } else {
                        $values[] = $value;
                    }
                }
            }
            if (empty($values)) {
                $this->emulateExecution();
            }
        } else {
            // composite keys

            // ensure keys of $this->link are prefixed the same way as $attributes
            $prefixedLink = array_combine(
                $attributes,
                array_values($this->link)
            );
            foreach ($models as $model) {
                $v = array();
                foreach ($prefixedLink as $attribute => $link) {
                    $v[$attribute] = $model[$link];
                }
                $values[] = $v;
                if (empty($v)) {
                    $this->emulateExecution();
                }
            }
        }
        $this->andWhere(array('in', $attributes, array_unique($values, SORT_REGULAR)));
    }

    /**
     * @param ActiveRecord|array $model
     * @param array $attributes
     * @return string
     */
    private function getModelKey($model, $attributes)
    {
        $key = array();
        foreach ($attributes as $attribute) {
            $key[] = $this->normalizeModelKey($model[$attribute]);
        }
        if (count($key) > 1) {
            return serialize($key);
        }
        $key = reset($key);
        return is_scalar($key) ? $key : serialize($key);
    }

    /**
     * @param mixed $value raw key value.
     * @return string normalized key value.
     */
    private function normalizeModelKey($value)
    {
        if (is_object($value) && method_exists($value, '__toString')) {
            // ensure matching to special objects, which are convertable to string, for cross-DBMS relations, for example: `|MongoId`
            $value = $value->__toString();
        }
        return $value;
    }

    /**
     * @param array $primaryModels either array of AR instances or arrays
     * @return array
     */
    private function findJunctionRows($primaryModels)
    {
        if (empty($primaryModels)) {
            return array();
        }
        $this->filterByModels($primaryModels);
        /* @var $primaryModel ActiveRecord */
        $primaryModel = reset($primaryModels);
        if (!$primaryModel instanceof ActiveRecord) {
            // when primaryModels are array of arrays (asArray case)
            $primaryModel = $this->modelClass;
        }

        return $this->asArray()->all(ArSql::getSqlHandler());
    }


}

