<?php

use Phpmig\Migration\Migration;

class CreateFeeds extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = 'CREATE TABLE feeds (id integer PRIMARY KEY AUTOINCREMENT, favicon_id integer, feed_type varchar(4) not null, title varchar(255) not null, url varchar(255) unique not null, site_url varchar(255) not null, is_spark tinyint(1) DEFAULT 0, last_updated_on_time timestamp not null)';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = 'DROP TABLE feeds';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }
}
