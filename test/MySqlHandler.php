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
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function queryOne($sql) {
        return $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function queryColumn($sql) {
        throw new NotSupportedException();
    }

    public function queryScalar($sql) {
        return $this->pdo->query($sql)->fetchColumn();
    }

    public function execute($sql) {
        return $this->pdo->exec($sql);
    }

    public function getLastInsertID() {
        return $this->pdo->lastInsertId();
    }

}