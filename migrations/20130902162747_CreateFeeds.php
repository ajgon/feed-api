<?php

use Phpmig\Migration\Migration;

class CreateFeeds extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = 'CREATE TABLE feeds (id integer PRIMARY KEY AUTOINCREMENT, favicon_id integer, title varchar(255), url varchar(255), site_url varchar(255), is_spark tinyint(1), last_updated_on_time timestamp)';
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
