<?php

namespace arSql;

use InvalidArgumentException;

class Command {

    /**
     * @var array the parameters (name => value) that are bound to the current PDO statement.
     * This property is maintained by methods such as [[bindValue()]]. It is mainly provided for logging purpose
     * and is used to generate [[rawSql]]. Do not modify it directly.
     */
    public $params = [];

    /**
     * @var array pending parameters to be bound to the current PDO statement.
     */
    private $_pendingParams = [];

    /**
     * @var string the SQL statement that this command represents
     */
    private $_sql;


    protected $sqlHandler;

    public function __construct(ISqlHandler $sqlHandler) {
        $this->sqlHandler = $sqlHandler;
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
            $this->_pendingParams = [];
            $this->params = [];
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
        $params = [];
        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }
            if (is_string($value)) {
                $params[$name] = $this->db->quoteValue($value);
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
     * Binds a parameter to the SQL statement to be executed.
     * @param string|int $name parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form `:name`. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value the PHP variable to bind to the SQL statement parameter (passed by reference)
     * @param int $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the value.
     * @param int $length length of the data type
     * @param mixed $driverOptions the driver-specific options
     * @return $this the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindParam.php
     */
    public function bindParam($name, &$value, $dataType = null, $length = null, $driverOptions = null)
    {
        $this->prepare();

        if ($dataType === null) {
            $dataType = $this->db->getSchema()->getPdoType($value);
        }
        if ($length === null) {
            $this->pdoStatement->bindParam($name, $value, $dataType);
        } elseif ($driverOptions === null) {
            $this->pdoStatement->bindParam($name, $value, $dataType, $length);
        } else {
            $this->pdoStatement->bindParam($name, $value, $dataType, $length, $driverOptions);
        }
        $this->params[$name] =& $value;

        return $this;
    }

    /**
     * Binds a value to a parameter.
     * @param string|int $name Parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form `:name`. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter
     * @param int $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the value.
     * @return $this the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindValue.php
     */
    public function bindValue($name, $value, $dataType = null)
    {
        if ($dataType === null) {
            $dataType = $this->db->getSchema()->getPdoType($value);
        }
        $this->_pendingParams[$name] = [$value, $dataType];
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
    public function bindValues($values)
    {
        if (empty($values)) {
            return $this;
        }

        $schema = $this->db->getSchema();
        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $this->_pendingParams[$name] = $value;
                $this->params[$name] = $value[0];
            } else {
                $type = $schema->getPdoType($value);
                $this->_pendingParams[$name] = [$value, $type];
                $this->params[$name] = $value;
            }
        }

        return $this;
    }

    public function insert($table, $columns) {
        $params = [];
        $sql = $this->db->getQueryBuilder()->insert($table, $columns, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    public function queryAll() {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->queryAll($rawSql);
    }

    public function queryOne($sql) {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->queryOne($rawSql);
    }

    public function queryColumn($sql) {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->queryColumn($rawSql);
    }

    public function queryScalar($sql) {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->queryScalar($rawSql);
    }

    public function execute($sql) {
        $rawSql = $this->getRawSql();
        return $this->sqlHandler->execute($rawSql);
    }

    public function getLastInsertID() {
        return $this->sqlHandler->getLastInsertID();
    }

}
