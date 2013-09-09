<?php

use Phpmig\Migration\Migration;

class CreateFeedsGroups extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = 'CREATE TABLE feeds_groups (feed_id integer, group_id integer, PRIMARY KEY (feed_id, group_id))';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = 'DROP TABLE feeds_groups';
        $container = $this->getContainer();
        $container['db']->query($sql);
    }
}
