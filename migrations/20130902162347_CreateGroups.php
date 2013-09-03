<?php

use Phpmig\Migration\Migration;

class CreateGroups extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = 'CREATE TABLE groups (id integer PRIMARY KEY AUTOINCREMENT, title varchar(255))';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = 'DROP TABLE groups';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }
}
