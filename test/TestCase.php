<?php

namespace test;

use PHPUnit_Framework_TestCase;
use arSql\Command;

abstract class TestCase extends PHPUnit_Framework_TestCase {

    protected static $command;

    public static function setUpBeforeClass() {
        if (!static::$command) {
            static::$command = new Command(new MySqlHandler());
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

}
