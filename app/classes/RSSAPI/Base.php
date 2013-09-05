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
     * Constructor
     *
     * @param string $main_dir Main application directory
     *
     * @return null
     */
    public function __construct($main_dir = null) {
        if (is_null($main_dir)) {
            $main_dir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR . '..');
        }
        $this->_main_dir = $main_dir;
    }

    /**
     * Init application.
     *
     * @return null
     */
    public function init()
    {
        $this->_response = new Response(self::API_VERSION, isset($_GET['api']) ? $_GET['api'] : 'json');

        $this->initDatabase();

        if ($this->authenticate()) {
            $this->_response->includeLastRefreshsedOnTime($_POST['api_key']);

            if (isset($_POST['mark'], $_POST['as'], $_POST['id'])) {
                $this->_response->mark($_POST['mark'], $_POST['as'], $_POST['id'], isset($_POST['before']) ? $_POST['before'] : null);
                if($_POST['mark'] === 'item') {
                    switch($_POST['as']) {
                    case 'unread':
                    case 'read':
                        $this->_response->includeUnreadItemIds();
                        break;
                    case 'unsaved':
                    case 'saved':
                        $this->_response->includeSavedItemIds();
                        break;
                    }
                }
            }

            if (isset($_GET['groups'])) {
                $this->_response->includeGroups();
                $this->_response->includeFeedsGroups();
            }
            if (isset($_GET['feeds'])) {
                $this->_response->includeFeedsGroups();
                $this->_response->includeFeeds();
            }
            if (isset($_GET['favicons'])) {
                $this->_response->includeFavicons();
            }
            if (isset($_GET['items'])) {
                $since_id = isset($_GET['since_id']) ? $_GET['since_id'] : null;
                $max_id = isset($_GET['max_id']) ? $_GET['max_id'] : null;
                $with_ids = isset($_GET['with_ids']) ? explode(',', $_GET['with_ids']) : null;
                $this->_response->includeItems(false, $since_id, $max_id, $with_ids);
            }
            if (isset($_GET['links'])) {
                // not implemented
                $this->_response->includeLinks();
            }
            if (isset($_GET['unread_item_ids'])) {
                $this->_response->includeUnreadItemIds();
            }
            if (isset($_GET['saved_item_ids'])) {
                $this->_response->includeSavedItemIds();
            }
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
     *
     * @return null
     */
    public function initDatabase()
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
        $db_config['connection_string'] = preg_replace('/^sqlite:/', 'sqlite:' . $this->_main_dir . '/', $db_config['connection_string']);
        \ORM::configure($db_config);
    }
}
