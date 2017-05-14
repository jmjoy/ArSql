<?php

namespace arSql;

use InvalidArgumentException;
use arSql\Builder;
use arSql\contract\ISqlHandler;
use arSql\exception\NotSupportedException;

class Command {

    /**
     * @var array the parameters (name => value) that are bound to the current PDO statement.
     * This property is maintained by methods such as [[bindValue()]]. It is mainly provided for logging purpose
     * and is used to generate [[rawSql]]. Do not modify it directly.
     */
    public $params = array();

    /**
     * @var array pending parameters to be bound to the current PDO statement.
     */
    private $_pendingParams = array();

    /**
     * @var string the SQL statement that this command represents
     */
    private $_sql;

    protected $sqlHandler;

    protected $schema;

    protected $builder;

    public function __construct(ISqlHandler $sqlHandler, $sql = '', $params = array()) {
        $this->sqlHandler = $sqlHandler;
        $schemaType = $this->sqlHandler->schemaType();
        $schemaClass = "\\arSql\\{$schemaType}\\Schema";
        if (!class_exists($schemaClass)) {
            throw new NotSupportedException("Not supported schema type: {$schemaType}");
        }
        $this->schema = new $schemaClass();
        $this->builder = new Builder($this->schema);

        if ($sql) {
            $this->setSql($sql)->bindValues($params);
        }
    }

    public function getSchema() {
        return $this->schema;
    }

    public function getBuilder() {
        return $this->builder;
    }

    /**
     * Returns the SQL statement for this command.
     * @return string the SQL statement to be executed
     */
    public function getSql() {
        return $this->_sql;
    }

    /**
     * Specifies the SQL statement to be executed.
     * The previous SQL execution (if any) will be cancelled, and [[params]] will be cleared as well.
     * @param string $sql the SQL statement to be set.
     * @return $this this command instance
     */
    public function setSql($sql) {
        if ($sql !== $this->_sql) {
            // $this->_sql = $this->db->quoteSql($sql);
            $this->_sql = $sql;
            $this->_pendingParams = array();
            $this->params = array();
        }

        return $this;
    }

    /**
     * Returns the raw SQL by inserting parameter values into the corresponding placeholders in [[sql]].
     * Note that the return value of this method should mainly be used for logging purpose.
     * It is likely that this method returns an invalid SQL due to improper replacement of parameter placeholders.
     * @return string the raw SQL with parameter values inserted into the corresponding placeholders in [[sql]].
     */
    public function getRawSql() {
        if (empty($this->params)) {
            return $this->_sql;
        }
        $params = array();
        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }
            if (is_string($value)) {
                $params[$name] = $this->schema->quoteValue($value);
            } elseif (is_bool($value)) {
                $params[$name] = ($value ? 'TRUE' : 'FALSE');
            } elseif ($value === null) {
                $params[$name] = 'NULL';
            } elseif (!is_object($value) && !is_resource($value)) {
                $params[$name] = $value;
            }
        }
        if (!isset($params[1])) {
            return strtr($this->_sql, $params);
        }
        $sql = '';
        foreach (explode('?', $this->_sql) as $i => $part) {
            $sql .= (isset($params[$i]) ? $params[$i] : '') . $part;
        }

        return $sql;
    }

    /**
     * Binds a value to a parameter.
     * @param string|int $name Parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form `:name`. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter
     * @return $this the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindValue.php
     */
    public function bindValue($name, $value) {
        // $this->_pendingParams[$name] = array($value, $dataType);
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Binds a list of values to the corresponding parameters.
     * This is similar to [[bindValue()]] except that it binds multiple values at a time.
     * Note that the SQL data type of each value is determined by its PHP type.
     * @param array $values the values to be bound. This must be given in terms of an associative
     * array with array keys being the parameter names, and array values the corresponding parameter values,
     * e.g. `[':name' => 'John', ':age' => 25]`. By default, the PDO type of each value is determined
     * by its PHP type. You may explicitly specify the PDO type by using an array: `[value, type]`,
     * e.g. `[':name' => 'John', ':profile' => [$profile, \PDO::PARAM_LOB]]`.
     * @return $this the current command being executed
     */
    public function bindValues($values) {
        if (empty($values)) {
            return $this;
        }

        $schema = $this->schema;
        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $this->_pendingParams[$name] = $value;
                $this->params[$name] = $value[0];
            } else {
                // $type = $schema->getPdoType($value);
                $type = ''; // TODO Want to delete _pendingParams;
                $this->_pendingParams[$name] = array($value, $type);
                $this->params[$name] = $value;
            }
        }

        return $this;
    }

    /**
     * Creates an INSERT command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->insert('user', [
     *     'name' => 'Sam',
     *     'age' => 30,
     * ])->execute();
     * ```
     *
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|\yii\db\Query $columns the column data (name => value) to be inserted into the table or instance
     * of [[yii\db\Query|Query]] to perform INSERT INTO ... SELECT SQL statement.
     * Passing of [[yii\db\Query|Query]] is available since version 2.0.11.
     * @return $this the command object itself
     */
    public function insert($table, $columns) {
        $params = array();
        $sql = $this->builder->insert($table, $columns, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates a batch INSERT command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->batchInsert('user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ])->execute();
     * ```
     *
     * The method will properly escape the column names, and quote the values to be inserted.
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * Also note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column names
     * @param array $rows the rows to be batch inserted into the table
     * @return $this the command object itself
     */
    public function batchInsert($table, $columns, $rows) {
        $sql = $this->builder->batchInsert($table, $columns, $rows);

        return $this->setSql($sql);
    }

    /**
     * Creates an UPDATE command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->update('user', ['status' => 1], 'age > 30')->execute();
     * ```
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     * @return $this the command object itself
     */
    public function update($table, $columns, $condition = '', $params = array()) {
        $sql = $this->builder->update($table, $columns, $condition, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates a DELETE command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->delete('user', 'status = 0')->execute();
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $table the table where the data will be deleted from.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     * @return $this the command object itself
     */
    public function delete($table, $condition = '', $params = array()) {
        $sql = $this->builder->delete($table, $condition, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    public function queryAll() {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->queryAll($rawSql);
    }

    public function queryOne() {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->queryOne($rawSql);
    }

    public function queryColumn() {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->queryColumn($rawSql);
    }

    public function queryScalar() {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->queryScalar($rawSql);
    }

    public function execute() {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->execute($rawSql);
    }

    public function getLastInsertID() {
        return $this->sqlHandler->getLastInsertID();
    }

}
