<?php

use Phpmig\Migration\Migration;

class CreateFavicons extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = 'CREATE TABLE favicons (id integer PRIMARY KEY AUTOINCREMENT, data text)';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = 'DROP TABLE favicons';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }
}
