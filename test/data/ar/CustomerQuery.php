<?php

namespace test\data\ar;

use arSql\ActiveQuery;

/**
 * CustomerQuery
 */
class CustomerQuery extends ActiveQuery
{
    public function active()
    {
        $this->andWhere('status=1');

        return $this;
    }
}


