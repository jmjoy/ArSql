<?php

namespace arSql;

use arSql\contract\ISqlHandler;
use arSql\exception\InvalidParamException;

class ArSql {

    protected static $sqlHandler;

    public static function registerSqlHandler(ISqlHandler $sqlHandler) {
        static::$sqlHandler = $sqlHandler;
    }

    public static function getSqlHandler() {
        if (!static::$sqlHandler) {
            throw new InvalidParamException('Empty SqlHandler, please set before get.');
        }
        return static::$sqlHandler;
    }

    public static function createCommand($sql = '', $params = array()) {
        return new Command(static::getSqlHandler(), $sql, $params);
    }

    public static function t($category, $message, $params = array(), $language = null) {
        return $message;
    }

}