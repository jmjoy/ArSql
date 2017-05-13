<?php

namespace arSql\contract;

interface ISqlHandler {

    const SCHEMA_MYSQL = 'mysql';

    public function schemaType();

    public function queryAll($sql);

    public function queryOne($sql);

    public function queryColumn($sql);

    public function queryScalar($sql);

    public function execute($sql);

    public function getLastInsertID();

}