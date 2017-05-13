<?php

return array(
    'database' => array(
        'mysql' => array(
            'dsn' => 'mysql:host=mysql;dbname=test',
            'username' => 'root',
            'password' => 'root',
            'fixture' => __DIR__ . '/mysql.sql',
        ),
    ),
);
