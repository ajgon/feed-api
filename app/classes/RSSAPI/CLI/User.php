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
namespace RSSAPI\CLI;

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
     * ./rssapi user add [user email] <user password>
     * Adds given user to the database.
     *
     * @return null
     */
    public function add()
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

        \RSSAPI\Data::addToDatabase(array(
            'user' => array(
                'email' => self::$param,
                'api_key' => md5(self::$param . ':' . $password)
            )
        ));
    }

    /**
     * ./rssapi user show
     * Lists all users in database.
     *
     * @return null
     */
    public function show()
    {
        $users = \ORM::for_table('users')->find_array();

        foreach ($users as $u => $user) {
            echo ($u + 1) . '. ' . $user['email'] . "\n";
        }
    }

    /**
     * ./rssapi user remove
     * Lists all users in database and allows user to delete unnecessary one.
     *
     * @return null
     */
    public function remove()
    {
        $user_id = $this->getUserIDFromUser();

        if ($user_id > 0) {
            \ORM::for_table('users')->where('id', $user_id)->delete_many();
        }
    }

    /**
     * Fetches group id chosen by user from the groups list.
     *
     * @return integer group ID
     */
    public function getUserIDFromUser()
    {
        $users = \ORM::for_table('users')->find_array();

        $list = array_map(create_function('$i', 'return $i[\'email\'];'), $users);

        $index = $this->userDetermine($list, false);
        return (int)$users[$index]['id'];
    }
}
