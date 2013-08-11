<?php
/**
 * Base class file.
 *
 * PHP version 5.3
 *
 * @category Core
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
namespace RSSAPI;

/**
 * Main application class, used to handle requests and responses.
 *
 * @category Core
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class Base
{
    const API_VERSION = '3';

    private $_main_dir = null;

    /**
     * Init application.
     */
    public function init($main_dir = null)
    {
        if (is_null($main_dir)) {
            $main_dir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR . '..');
        }
        $this->_main_dir = $main_dir;
        $this->_response = new Response(self::API_VERSION, isset($_GET['api']) ? $_GET['api'] : 'json');

        $this->initDatabase();

        if ($this->authenticate()) {

        }

        $this->_response->render();
    }

    /**
     * Authenticate API Call
     *
     * @return boolean Authenticated?
     */
    private function authenticate()
    {
        $auth = new Auth(isset($_POST['api_key']) ? $_POST['api_key'] : '');
        $this->_response->setAuth($auth->validate());

        return $auth->validate();
    }

    /**
     * Initialize database
     */
    private function initDatabase()
    {
        $user_config = require $this->_main_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        $auto_config = array(
            'return_result_sets' => true,
            'driver_options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            )
        );
        $db_config = array_merge(
            $user_config,
            $auto_config
        );
        $db_config['connection_string'] = preg_replace('/^sqlite:/', 'sqlite:../', $db_config['connection_string']);
        \ORM::configure($db_config);
    }
}
