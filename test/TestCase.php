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

    protected static $mysqlConfig;

    public static function setUpBeforeClass() {
        if (!static::$initialized) {
            static::$initialized = true;

            $config = require __DIR__ . '/data/config.php';
            $mysqlConfig = $config['database']['mysql'];
            static::$mysqlConfig = $mysqlConfig;
            static::$pdo = new PDO($mysqlConfig['dsn'], $mysqlConfig['username'], $mysqlConfig['password']);

            static::$sqlHandler = new MySqlHandler(static::$pdo);
            static::$command = new Command(static::$sqlHandler);
            ArSql::registerSqlHandler(static::$sqlHandler);

            static::prepareDatabase($mysqlConfig['fixture']);
        }
    }

    public static function tearDownAfterClass() {
    }

    // public function setUp() {
    //     static::prepareDatabase(static::$mysqlConfig['fixture']);
    // }

    /**
     * adjust dbms specific escaping
     * @param $sql
     * @return mixed
     */
    protected function replaceQuotes($sql) {
        return str_replace(array('[[', ']]'), '`', $sql);
    }

    protected static function prepareDatabase($fixture = null) {
        if (!$fixture) {
            $fixture = static::$mysqlConfig['fixture'];
        }
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
