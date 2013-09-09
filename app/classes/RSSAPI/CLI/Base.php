<?php
/**
 * Base class for CLI actions.
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
 * Base class for CLI actions.
 *
 * @category CLI
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class Base
{
    static protected $action;
    static protected $object;
    static protected $param;
    static protected $command;

    /**
     * Displays error if something unexpected occures.
     *
     * @param  string $message Error message
     * @param  string $param   If provided, it will replace [parameter] field in CLI usage description.
     *
     * @return null
     */
    protected function error($message, $param = 'parameter')
    {
        file_put_contents('php://stderr', $message . "\n");
        if($param) {
            $this->showUsage($param);
        }
        // TODO: Fix this
        die;
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
    protected function fetchFeedLink($url) {
        $items = \RSSAPI\Parser::fetchFeedLink($url);

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
    protected function userDetermine($list, $autoCommit = true) {
        $listSize = count($list);
        $opt = -1;

        if ($listSize == 0) {
            throw new \RSSAPI\Exception('Empty list to determine!');
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
     * Shows CLI usage line
     *
     * @param  string $param If provided, it will replace [parameter] field in CLI usage description.
     *
     * @return null
     */
    protected function showUsage($param = 'parameter') {
        echo 'Usage: ' . self::$command . " <object> <action> [{$param}]\n";
        //echo 'Type ' . self::$command . " <object> help - to see detailed info.\n"; //TODO
        echo "Objects: feed, group, user\n";
        echo "Actions: \n";
        echo "         feed: add, fetch, show, remove\n";
        //echo "        group: add, attach, show, remove\n";
        //echo "         user: add, remove\n";
    }
}
