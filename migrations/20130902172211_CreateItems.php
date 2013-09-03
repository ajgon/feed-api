<?php

use Phpmig\Migration\Migration;

class CreateItems extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = 'CREATE TABLE items (id integer PRIMARY KEY AUTOINCREMENT, feed_id integer, title varchar(255), author varchar(255), html text, url varchar(4096), is_saved tinyint(1), is_read tinyint(1), created_on_time timestamp, added_on_time timestamp)';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = 'DROP TABLE items';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }
}
