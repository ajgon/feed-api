<?php
/**
 * Command line interface items class file.
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
 * Class used to handle CLI commands.
 *
 * @category Core
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class CLI
{
    private $_action;
    private $_param;
    private $_command;

    /**
     * Constructor
     *
     * @param array $args CLI arguments, basically $argv
     *
     * @return null
     */
    public function __construct($args)
    {
        $this->_command = $args[0];
        $this->_action = $args[1];
        $this->_param = $args[2];
        $this->initDatabase();
    }

    /**
     * ./rssapi add [site/rss url]
     * Adds given url feed to the database.
     *
     * @return null
     */
    public function add()
    {
        if (empty($this->_param)) {
            $this->error('Please provide URL of site/RSS channel which you wish to add.', 'site/rss url');
            return;
        }

        $linkData = $this->fetchFeedLink($this->_param);

        if ($linkData) {
            $parserName = '\\RSSAPI\\Parsers\\' . $linkData['type'];
            $parser = new $parserName();
            $items = $parser->parseLink($linkData['url']);
            unset($items['items']);

            Data::addToDatabase($items);
        }
    }

    /**
     * ./rssapi fetch
     * Fetches new or updated items for feeds in database.
     *
     * @return null
     */
    public function fetch()
    {
        $feeds = \ORM::for_table('feeds')->find_array();

        foreach ($feeds as $feed) {
            $parserName = '\\RSSAPI\\Parsers\\' . $feed['feed_type'];
            $parser = new $parserName();
            $items = $parser->parseLink($feed['url']);

            Data::addToDatabase($items);
        }
    }

    /**
     * ./rssapi list
     * Lists all feeds in database.
     *
     * @return null
     */
    public function listfeeds()
    {
        $feeds = \ORM::for_table('feeds')->find_array();

        foreach ($feeds as $f => $feed) {
            echo ($f + 1) . '. ' . $feed['title'] . ' (' .$feed['url'] . ")\n";
        }
    }

    /**
     * ./rssapi delete
     * Lists all feeds in database and allows user to delete unnecessary one.
     *
     * @return null
     */
    public function remove() {
        $feeds = \ORM::for_table('feeds')->find_array();
        $items = array();

        foreach ($feeds as $f => $feed) {
            $items[] = array(
                'id' => $feed['id'],
                'name' => $feed['title'] . ' (' .$feed['url'] . ')'
            );
        }

        $list = array_map(create_function('$i', 'return $i[\'name\'];'), $items);

        $index = $this->userDetermine($list, false);
        $feed_id = (int)$items[$index]['id'];

        if ($feed_id > 0) {
            \ORM::for_table('items')->where('feed_id', $feed_id)->delete_many();
            \ORM::for_table('feeds_groups')->where('feed_id', $feed_id)->delete_many();
            \ORM::for_table('feeds')->where('id', $feed_id)->delete_many();
        }
    }

    /**
     * Processes CLI invocation, launches corresponding action.
     *
     * @return null
     */
    public function process()
    {
        if (empty($this->_action)) {
            $this->showUsage();
            return;
        }

        try {
            switch($this->_action) {
            case 'add':
                $this->add();
                break;
            case 'fetch':
                $this->fetch();
                break;
            case 'list':
                $this->listfeeds();
                break;
            case 'delete':
                $this->remove();
                break;
            default:
                $this->error('Unknown action.');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }

    }

    /**
     * Displays error if something unexpected occures.
     *
     * @param  string $message Error message
     * @param  string $param   If provided, it will replace [parameter] field in CLI usage description.
     *
     * @return null
     */
    private function error($message, $param = 'parameter')
    {
        file_put_contents('php://stderr', $message . "\n");
        if($param) {
            $this->showUsage($param);
        }
        // TODO: Fix this
        die;
    }

    /**
     * Shows CLI usage line
     *
     * @param  string $param If provided, it will replace [parameter] field in CLI usage description.
     *
     * @return null
     */
    private function showUsage($param = 'parameter') {
        echo "Usage: {$this->_command} <action> [{$param}]\n";
        echo "Actions: add, fetch, list, delete\n";
    }

    /**
     * Fetches all links for specified URL. If more than one Feed link is found,
     * user is allowed to choose the one he wishes. Then function returns linkData
     * related to that link.
     *
     * @param  string $url URL for HTML/Feed
     *
     * @return array Link data
     */
    private function fetchFeedLink($url) {
        $items = Parser::fetchFeedLink($url);

        if (count($items) == 1) {
            return $items[0];
        }

        $list = array();
        foreach ($items as $item) {
            $list[] = $item['title'] . ' (' . $item['url'] . ')';
        }

        if (empty($list)) {
            $this->error('No RSS data found under provided URL.');
            return;
        }

        $index = $this->userDetermine($list);

        if ($index == -1) {
            return false;
        }

        return $items[$index];
    }

    /**
     * Displays a list of options for user, and asks him to choose one.
     *
     * @param  array   $list       List of options
     * @param  boolean $autoCommit If list is one-elem only, return this element index
     *
     * @return integer Chosen option index
     */
    private function userDetermine($list, $autoCommit = true) {
        $listSize = count($list);
        $opt = -1;

        if ($listSize == 0) {
            throw new Exception('Empty list to determine!');
        }

        if ($listSize == 1 && $autoCommit) {
            return 0;
        }

        while($opt < 0 || $opt > $listSize) {
            foreach ($list as $l => $litem) {
                echo $l + 1 . ". {$litem}\n";
            }
            echo "0. Cancel\n";

            $fh = fopen('php://stdin', 'r');
            $line = fgets($fh);
            $opt = (int)trim($line);
            fclose($fh);
        }

        return $opt - 1;
    }

    /**
     * Inits database for CLI.
     *
     * @return null
     */
    private function initDatabase() {
        $base = new Base();
        $base->initDatabase();
    }
}
