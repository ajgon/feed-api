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
    }

    /**
     * ./rssapi add [site/rss url]
     * Adds given url feed to the database.
     *
     * @return null
     */
    public function add() {
        if (empty($this->_param)) {
            $this->error('Please provide URL of site/RSS channel which you wish to add.', 'site/rss url');
            return;
        }

        try {
            $linkData = $this->fetchFeedLink($this->_param);

            $parserName = '\\RSSAPI\\Parsers\\' . $linkData['type'];
            $parser = new $parserName();
            $items = $parser->parseLink($linkData['url']);

            Data::addToDatabase($items);
        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
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

        switch($this->_action) {
        case 'add':
            $this->add();
            break;
        default:
            $this->error('Unknown action.');
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
    }

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

        return $items[$index];
    }

    /**
     * Data::fetch wrapper - includes CLI errors display.
     *
     * @param  string $url URL to fetch
     *
     * @return null
     */
    private function fetchData($url) {
        try {
            return Data::fetch($url);
        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }
    }

    /**
     * Displays a list of options for user, and asks him to choose one.
     *
     * @param  array $list List of options
     *
     * @return integer Chosen option index
     */
    private function userDetermine($list) {
        $listSize = count($list);
        if ($listSize == 0) {
            throw new Exception('Empty list to determine!');
        }

        if ($listSize == 1) {
            return 0;
        }

        while($opt < 1 || $opt > $listSize) {
            foreach ($list as $l => $litem) {
                echo $l + 1 . ". {$litem}\n";
            }

            $fh = fopen('php://stdin', 'r');
            $line = fgets($fh);
            $opt = (int)trim($line);
            fclose($fh);
        }

        return $opt - 1;
    }
}
