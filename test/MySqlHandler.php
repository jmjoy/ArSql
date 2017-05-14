<?php

namespace test;

use PDO;
use Exception;
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
        $query = $this->pdo->query($sql);
        $this->checkFail($query, $sql);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function queryOne($sql) {
        $query = $this->pdo->query($sql);
        $this->checkFail($query, $sql);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function queryColumn($sql) {
        $query = $this->pdo->query($sql);
        $this->checkFail($query, $sql);
        return $query->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function queryScalar($sql) {
        $query = $this->pdo->query($sql);
        $this->checkFail($query, $sql);
        return $query->fetchColumn();
    }

    public function execute($sql) {
        $exec = $this->pdo->exec($sql);
        $this->checkFail($exec, $sql);
        return $exec;
    }

    public function getLastInsertID() {
        return $this->pdo->lastInsertId();
    }

    protected function checkFail($query, $sql) {
        if ($query === false) {
            dump($sql);
            dump($this->pdo->errorInfo());
        }
    }

}