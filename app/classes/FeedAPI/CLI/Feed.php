<?php
/**
 * Feed related actions for CLI.
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
 * Class used to handle CLI commands related to feeds.
 *
 * @category CLI
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class Feed extends Base
{

    /**
     * ./feedapi feed add [site/rss url]
     * Adds given url feed to the database.
     *
     * @return null
     */
    public function add()
    {
        if (empty(self::$param)) {
            $this->error('Please provide URL of site/RSS channel which you wish to add.', 'site/rss url');
            return;
        }

        $result = $this->fetchFeedData(self::$param);
        $linkData = $result['data'];
        $linkFavicon = $result['favicon'];

        if ($linkData) {
            $parserName = '\\FeedAPI\\Parsers\\' . $linkData['type'];
            $parser = new $parserName();
            $items = $parser->parseLink($linkData['url']);
            $items['feed']['favicon_id'] = $this->addFavicon($linkFavicon);
            unset($items['items']);

            \FeedAPI\Data::addToDatabase($items);
        }
    }

    /**
     * ./feedapi feed fetch
     * Fetches new or updated items for feeds in database.
     *
     * @return null
     */
    public function fetch()
    {
        $feeds = \ORM::for_table('feeds')->find_array();

        foreach ($feeds as $feed) {
            $parserName = '\\FeedAPI\\Parsers\\' . $feed['feed_type'];
            $parser = new $parserName();
            $items = $parser->parseLink($feed['url']);

            \FeedAPI\Data::addToDatabase($items, true);
        }
    }

    /**
     * ./feedapi feed show
     * Lists all feeds in database.
     *
     * @return null
     */
    public function show()
    {
        $feeds = \ORM::for_table('feeds')->find_array();

        foreach ($feeds as $f => $feed) {
            echo ($f + 1) . '. ' . $feed['title'] . ' (' .$feed['url'] . ")\n";
        }
    }

    /**
     * ./feedapi feed remove
     * Lists all feeds in database and allows user to delete unnecessary one.
     *
     * @return null
     */
    public function remove()
    {
        $feed_id = $this->getFeedIDFromUser();

        if ($feed_id > 0) {
            \ORM::for_table('items')->where('feed_id', $feed_id)->delete_many();
            \ORM::for_table('feeds_groups')->where('feed_id', $feed_id)->delete_many();
            \ORM::for_table('feeds')->where('id', $feed_id)->delete_many();
        }
    }

    /**
     * ./feedapi feed help
     * Displays short help describiing all available actions.
     *
     * @return null
     */
    public function help() {
        echo 'Usage: ' . self::$command . " feed <action> [site/rss url]\n";
        echo "Actions: \n";
        echo "  add [site/rss url] - will add feed to database. If URL to page is provided,  \n".
             "                       feedapi will determine all the feeds in that page and    \n".
             "                       offer a choice of the feed if multiple found. If only   \n".
             "                       one feed is found, it will be added automatically       \n";
        echo "  fetch              - fetches all new items for the feeds\n";
        echo "  show               - lists all feeds in database\n";
        echo "  remove             - allows user to delete feeds\n";
    }

    /**
     * Fetches feed id chosen by user from the feeds list.
     *
     * @return integer feed ID
     */
    public function getFeedIDFromUser($filter_by = false, $id = 0)
    {
        if(($filter_by == 'user' || $filter_by == 'group') && (int)$id > 0) {
            $join = 'feeds_' . $filter_by . 's';
            $feeds = \ORM::for_table('feeds')->join($join, array("{$join}.feed_id", '=', 'feeds.id'))->where("{$join}.{$filter_by}_id", $id)->find_array();
        } else {
            $feeds = \ORM::for_table('feeds')->find_array();
        }
        $items = array();

        foreach ($feeds as $f => $feed) {
            $items[] = array(
                'id' => $feed['id'],
                'name' => $feed['title'] . ' (' .$feed['url'] . ')'
            );
        }

        $list = array_map(create_function('$i', 'return $i[\'name\'];'), $items);

        $index = $this->userDetermine($list, false);
        return (int)$items[$index]['id'];
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
    private function fetchFeedData($url) {
        $items = \FeedAPI\Parser::fetchFeedData($url);
        $favicon = \FeedAPI\Parser::fetchFeedFavicon($url);

        if (count($items) == 1) {
            return array(
                'favicon' => $favicon,
                'data' => $items[0]
            );
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

        return array(
            'favicon' => $favicon,
            'data' => $items[$index]
        );
    }

    /**
     * Adds favicon from given URL to database and returns it's ID.
     *
     * @param string $url favicon URL
     *
     * @return integer favicon ID
     */
    private function addFavicon($url) {
        if ($url) {
            try {
                $result = \FeedAPI\Data::fetch($url);
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $favicon = array('data' => $finfo->buffer($result) . ';base64,' . base64_encode($result));
            } catch(Exception $e) {
                $favicon = array('data' => 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
            }
        } else {
            $favicon = array('data' => 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
        }

        $res = \FeedAPI\Data::addToDatabase(array('favicon' => $favicon));
        return (int)$res['favicon'];
    }
}
