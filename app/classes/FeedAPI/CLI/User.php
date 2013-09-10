<?php
/**
 * User related actions for CLI.
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
 * Class used to handle CLI commands related to users.
 *
 * @category CLI
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class User extends Base
{

    /**
     * ./feedapi user add [user email] <user password>
     * Adds given user to the database.
     *
     * @param boolean $super if true added user is a superuser (can see all the feeds).
     *
     * @return null
     */
    public function add($super = false)
    {
        if (empty(self::$param)) {
            $this->error('Please provide User email.', 'user email');
            return;
        }

        if (empty(self::$extra)) {
            $password = $this->hiddenPrompt();
        } else {
            $password = self::$extra;
        }

        if (empty($password)) {
            $this->error('Please provide User password.', false);
            return;
        }

        $c = \ORM::for_table('users')->where('email', self::$param)->count();
        if ($c > 0) {
            $this->error('User with this email already exists', false);
        }

        \FeedAPI\Data::addToDatabase(array(
            'user' => array(
                'email' => self::$param,
                'api_key' => md5(self::$param . ':' . $password),
                'super' => $super ? '1': '0'
            )
        ));
    }

    /**
     * ./feedapi user attach
     * Attaches given user to given feed.
     *
     * @return null
     */
    public function attach()
    {
        $feed = new Feed();
        $user_id = $this->getUserIDFromUser();
        $feed_id = $feed->getFeedIDFromUser();

        if ($user_id > 0 && $feed_id > 0) {
            $res = \ORM::for_table('feeds_users')->where('user_id', $user_id)->where('feed_id', $feed_id)->count();
            if ($res > 0) {
                throw new \FeedAPI\Exception('Relation already exists.');
            }
            $fg = \ORM::for_table('feeds_users')->create();
            $fg->feed_id = $feed_id;
            $fg->user_id = $user_id;
            $fg->save();
        }
    }

    /**
     * ./feedapi user detach
     * Removes given feed from given user.
     *
     * @return null
     */
    public function detach()
    {
        $feed = new Feed();
        $user_id = $this->getUserIDFromUser();
        $feed_id = $feed->getFeedIDFromUser('user', $user_id);

        if ($user_id > 0 && $feed_id > 0) {
            $res = \ORM::for_table('feeds_users')->where('user_id', $user_id)->where('feed_id', $feed_id)->delete_many();
        }
    }

    /**
     * ./feedapi user show
     * Lists all users in database.
     *
     * @return null
     */
    public function show()
    {
        $users = \ORM::for_table('users')->find_array();

        foreach ($users as $u => $user) {
            echo ($u + 1) . '. ' . $user['email'] . ($user['super'] ? ' (super)' : '') . "\n";
            if(!$user['super']) {
                $feeds = \ORM::for_table('feeds')->join('feeds_users', array('feeds_users.feed_id', '=', 'feeds.id'))->where('feeds_users.user_id', $user['id'])->find_array();
                foreach ($feeds as $f => $feed) {
                    echo "   - " . $feed['title'] . ' (' . $feed['url'] . ")\n";
                }
            }
        }
    }

    /**
     * ./feedapi user remove
     * Lists all users in database and allows user to delete unnecessary one.
     *
     * @return null
     */
    public function remove()
    {
        $user_id = $this->getUserIDFromUser();

        if ($user_id > 0) {
            \ORM::for_table('feeds_users')->where('user_id', $user_id)->delete_many();
            \ORM::for_table('users')->where('id', $user_id)->delete_many();
        }
    }

    /**
     * ./feedapi user help
     * Displays short help describiing all available actions.
     *
     * @return null
     */
    public function help() {
        echo 'Usage: ' . self::$command . " user <action> [email] <password>\n";
        echo "Actions: \n";
        echo "  add [email] <password>      - password is optional, if not provided, system  \n".
             "                                will prompt for it (useful if you don't want to\n".
             "                                leave your password in *_history logs)\n";
        echo "  addsuper [email] <password> - same as add, but added user is a super user    \n".
             "                                (he can see all the groups and feeds)\n";
        echo "  attach                      - displays list of users, then list of feeds. If \n".
             "                                both user and feed are chosen, selected feed   \n".
             "                                will be attached to selected user (he will be  \n".
             "                                able to fetch it)\n";
        echo "  detach                      - removes given feed from given user\n";
        echo "  show                        - lists all users\n";
        echo "  remove                      - removes user chosen by user\n";
    }

    /**
     * Fetches group id chosen by user from the groups list.
     *
     * @return integer group ID
     */
    public function getUserIDFromUser()
    {
        $users = \ORM::for_table('users')->find_array();

        $list = array_map(create_function('$i', 'return $i[\'email\'] . ($i[\'super\'] ? \' (super)\' : \'\');'), $users);

        $index = $this->userDetermine($list, false);
        return (int)$users[$index]['id'];
    }
}
