<?php

namespace arSql\contract;

interface ISqlHandler {

    const SCHEMA_MYSQL = 'mysql';

    public function schemaType();

    public function queryAll($sql);

    public function queryOne($sql);

    public function queryColumn($sql);

    public function queryScalar($sql);

    /**
     * 执行一条 SQL 语句，并返回受影响的行数
     */
    public function execute($sql);

    /**
     * 返回最后插入行的ID或序列值
     */
    public function getLastInsertID();

}