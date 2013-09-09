<?php

use Phpmig\Migration\Migration;

class CreateFeedsUsers extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = 'CREATE TABLE feeds_users (feed_id integer, user_id integer, PRIMARY KEY (feed_id, user_id))';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = 'DROP TABLE feeds_users';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }
}
