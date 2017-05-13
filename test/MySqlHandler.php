<?php

namespace test;

use arSql\contract\ISqlHandler;
use arSql\exception\NotSupportedException;

class MySqlHandler implements ISqlHandler {

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