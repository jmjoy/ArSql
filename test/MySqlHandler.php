<?php

namespace test;

use PDO;
use arSql\contract\ISqlHandler;
use arSql\exception\NotSupportedException;

class MySqlHandler implements ISqlHandler {

    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function schemaType() {
        return ISqlHandler::SCHEMA_MYSQL;
    }

    public function queryAll($sql) {
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
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
        return $this->pdo->exec($sql);
    }

    public function getLastInsertID() {
        throw new NotSupportedException();
    }

}