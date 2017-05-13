<?php

namespace test;

use arSql\Command;

class CommandTest extends TestCase {

    public function testInsert() {
        $this->assertNotEmpty(static::$command);
    }

}
