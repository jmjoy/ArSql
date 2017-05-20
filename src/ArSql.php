<?php

namespace arSql;

use arSql\contract\ISqlHandler;
use arSql\exception\InvalidParamException;
use arSql\exception\NotSupportedException;

class ArSql {

    protected static $tablePrefix = '';

    protected static $sqlHandler;

    protected static $schemas = array();

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

    public static function getSchema(ISqlHandler $sqlHandler = null) {
        if (!$sqlHandler) {
            $sqlHandler = static::getSqlHandler();
        }
        $schemaType = $sqlHandler->schemaType();
        if (!isset(static::$schemas[$schemaType])) {
            $schemaClass = "\\arSql\\{$schemaType}\\Schema";
            if (!class_exists($schemaClass)) {
                throw new NotSupportedException("Not supported schema type: {$schemaType}");
            }
            static::$schemas[$schemaType] = new $schemaClass($sqlHandler);
        }
        return static::$schemas[$schemaType];
    }

    public static function getBuilder(ISqlHandler $sqlHandler = null) {
        if (!$sqlHandler) {
            $sqlHandler = static::getSqlHandler();
        }
        $schemaType = $sqlHandler->schemaType();
        $className = "\\arSql\\{$schemaType}\\Builder";
        if (!class_exists($className)) {
            throw new NotSupportedException("Not supported schema type: {$schemaType}");
        }
        return new $schemaClass($sqlHandler);
    }

    public static function getTablePrefix() {
        return static::$tablePrefix;
    }

    public static function setTablePrefix($tablePrefix) {
        static::$tablePrefix = $tablePrefix;
    }

    public static function insert($table, $columns) {
        return static::createCommand()->insert($table, $columns);
    }

    public static function batchInsert($table, $columns, $rows) {
        return static::createCommand()->batchInsert($table, $columns, $rows);
    }

    public static function update($table, $columns, $condition = '', $params = array()) {
        return static::createCommand()->update($table, $columns, $condition, $params);
    }

    public static function delete($table, $condition = '', $params = array()) {
        return static::createCommand()->delete($table, $condition, $params);
    }

}