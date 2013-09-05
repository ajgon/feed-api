<?php

use Phpmig\Migration\Migration;

class CreateItems extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = 'CREATE TABLE items (id integer PRIMARY KEY AUTOINCREMENT, feed_id integer not null, rss_id varchar(255) unique not null, title varchar(255) not null, author varchar(255), html text not null, url varchar(4096) not null, is_saved tinyint(1) DEFAULT 0, is_read tinyint(1) DEFAULT 0, created_on_time timestamp not null, added_on_time timestamp not null)';
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
