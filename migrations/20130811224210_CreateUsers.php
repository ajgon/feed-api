<?php

use Phpmig\Migration\Migration;

class CreateUsers extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = 'CREATE TABLE users (id integer PRIMARY KEY AUTOINCREMENT, email varchar(255) unique, api_key char(32), last_refreshed_on_time timestamp DEFAULT 0)';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = 'DROP TABLE users';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }
}
