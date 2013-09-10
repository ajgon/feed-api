<?php
/**
 * Group related actions for CLI.
 *
 * PHP version 5.3
 *
 * @category CLI
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
namespace FeedAPI\CLI;

/**
 * Class used to handle CLI commands related to groups.
 *
 * @category CLI
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class Group extends Base
{

    /**
     * ./feedapi group add [group name]
     * Adds given group to the database.
     *
     * @return null
     */
    public function add()
    {
        if (empty(self::$param)) {
            $this->error('Please provide Group name.', 'group name');
            return;
        }

        \FeedAPI\Data::addToDatabase(array(
            'group' => array(
                'title' => self::$param
            )
        ));
    }

    /**
     * ./feedapi group attach
     * Attaches given group to given feed.
     *
     * @return null
     */
    public function attach()
    {
        $feed = new Feed();
        $group_id = $this->getGroupIDFromUser();
        $feed_id = $feed->getFeedIDFromUser();

        if ($group_id > 0 && $feed_id > 0) {
            $res = \ORM::for_table('feeds_groups')->where('group_id', $group_id)->where('feed_id', $feed_id)->count();
            if ($res > 0) {
                throw new \FeedAPI\Exception('Relation already exists.');
            }
            $fg = \ORM::for_table('feeds_groups')->create();
            $fg->feed_id = $feed_id;
            $fg->group_id = $group_id;
            $fg->save();
        }
    }

    /**
     * ./feedapi group show
     * Lists all groups in database.
     *
     * @return null
     */
    public function show()
    {
        $groups = \ORM::for_table('groups')->find_array();

        foreach ($groups as $g => $group) {
            echo ($g + 1) . '. ' . $group['title'] . "\n";
            $feeds = \ORM::for_table('feeds')->join('feeds_groups', array('feeds_groups.feed_id', '=', 'feeds.id'))->where('feeds_groups.group_id', $group['id'])->find_array();
            foreach ($feeds as $f => $feed) {
                echo "   - " . $feed['title'] . ' (' . $feed['url'] . ")\n";
            }
        }
    }

    /**
     * ./feedapi group remove
     * Lists all groups in database and allows user to delete unnecessary one.
     *
     * @return null
     */
    public function remove()
    {
        $group_id = $this->getGroupIDFromUser();

        if ($group_id > 0) {
            \ORM::for_table('feeds_groups')->where('group_id', $group_id)->delete_many();
            \ORM::for_table('groups')->where('id', $group_id)->delete_many();
        }
    }

    /**
     * ./feedapi group help
     * Displays short help describiing all available actions.
     *
     * @return null
     */
    public function help() {
        echo 'Usage: ' . self::$command . " group <action> [group_name]\n";
        echo "Actions: \n";
        echo "  add [group_name] - will add group to database\n";
        echo "  attach           - displays list of groups, then list of feeds. If both group\n".
             "                     and feed are chosen, selected feed will be attached to    \n".
             "                     selected group\n";
        echo "  show             - lists all groups with corresponding feeds\n";
        echo "  remove           - allows user to delete groups\n";
    }
    /**
     * Fetches group id chosen by user from the groups list.
     *
     * @return integer group ID
     */
    public function getGroupIDFromUser()
    {
        $groups = \ORM::for_table('groups')->find_array();

        $list = array_map(create_function('$i', 'return $i[\'title\'];'), $groups);

        $index = $this->userDetermine($list, false);
        return (int)$groups[$index]['id'];
    }
}
