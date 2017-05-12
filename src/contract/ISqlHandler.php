<?php

namespace arSql\contract;

interface ISqlHandler {

    public function queryAll($sql);

    public function queryOne($sql);

    public function queryColumn($sql);

    public function queryScalar($sql);

    public function execute($sql);

    public function getLastInsertID();

}