<?php
/**
 * Command line interface items class file.
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
 * Class used to handle CLI commands.
 *
 * @category CLI
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class CLI extends Base
{
    /**
     * Constructor
     *
     * @param array $args CLI arguments, basically $argv
     *
     * @return null
     */
    public function __construct($args)
    {
        parent::$command = isset($args[0]) ? $args[0] : false;
        parent::$object  = isset($args[1]) ? $args[1] : false;
        parent::$action  = isset($args[1]) ? $args[2] : false;
        parent::$param   = isset($args[2]) ? $args[3] : false;
        $this->initDatabase();
    }

    /**
     * Processes CLI invocation, launches corresponding action.
     *
     * @return null
     */
    public function process()
    {
        if (empty(self::$object) || empty(self::$action)) {
            $this->showUsage();
            return;
        }

        try {
            switch(self::$object) {
            case 'feed':
                $feed = new Feed();
                switch(self::$action) {
                case 'add':
                    $feed->add();
                    break;
                case 'fetch':
                    $feed->fetch();
                    break;
                case 'show':
                    $feed->show();
                    break;
                case 'remove':
                    $feed->remove();
                    break;
                default:
                    $this->error('Unknown action.');
                }
                break;
            default:
                $this->error('Unknown object.');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }

    }

    /**
     * Inits database for CLI.
     *
     * @return null
     */
    private function initDatabase() {
        $base = new \RSSAPI\Base();
        $base->initDatabase();
    }
}
