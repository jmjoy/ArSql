<?php

namespace test;

use PDO;
use arSql\contract\ISqlHandler;
use arSql\exception\NotSupportedException;

class MySqlHandler implements ISqlHandler {

    protected $pdo;

    public function __construct() {
        $config = require __DIR__ . '/data/config.php';
        $mysqlConfig = $config['database']['mysql'];
        $this->pdo = new PDO($mysqlConfig['dsn'], $mysqlConfig['username'], $mysqlConfig['password']);
    }

    public function schemaType() {
        return ISqlHandler::SCHEMA_MYSQL;
    }

    public function queryAll($sql) {
        throw new NotSupportedException();
    }

    public function queryOne($sql) {
        throw new NotSupportedException();
    }

    public function queryColumn($sql) {
        throw new NotSupportedException();
    }

    public function queryScalar($sql) {
        throw new NotSupportedException();
    }

    public function execute($sql) {
        throw new NotSupportedException();
    }

    public function getLastInsertID() {
        throw new NotSupportedException();
    }

}