<?php

namespace test;

use PDO;
use PHPUnit_Framework_TestCase;
use arSql\Command;
use arSql\ArSql;

abstract class TestCase extends PHPUnit_Framework_TestCase {

    protected static $initialized = false;

    protected static $pdo;

    protected static $command;

    protected static $sqlHandler;

    public static function setUpBeforeClass() {
        if (!static::$initialized) {
            static::$initialized = true;

            $config = require __DIR__ . '/data/config.php';
            $mysqlConfig = $config['database']['mysql'];
            static::$pdo = new PDO($mysqlConfig['dsn'], $mysqlConfig['username'], $mysqlConfig['password']);

            static::prepareDatabase($mysqlConfig['fixture']);

            static::$sqlHandler = new MySqlHandler(static::$pdo);
            static::$command = new Command(static::$sqlHandler);

            ArSql::registerSqlHandler(static::$sqlHandler);
        }
    }

    public static function tearDownAfterClass() {
    }

    /**
     * adjust dbms specific escaping
     * @param $sql
     * @return mixed
     */
    protected function replaceQuotes($sql) {
        return str_replace(array('[[', ']]'), '`', $sql);
    }

    private static function prepareDatabase($fixture) {
        $lines = explode(';', file_get_contents($fixture));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                if (static::$pdo->exec($line) === false) {
                    dump($line);
                    dump(static::$pdo->errorInfo());
                    die();
                }
            }
        }
    }

}
