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
namespace RSSAPI\CLI;

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
     * ./rssapi feed add [site/rss url]
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

        $linkData = $this->fetchFeedLink(self::$param);

        if ($linkData) {
            $parserName = '\\RSSAPI\\Parsers\\' . $linkData['type'];
            $parser = new $parserName();
            $items = $parser->parseLink($linkData['url']);
            unset($items['items']);

            \RSSAPI\Data::addToDatabase($items);
        }
    }

    /**
     * ./rssapi feed fetch
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

            \RSSAPI\Data::addToDatabase($items);
        }
    }

    /**
     * ./rssapi feed show
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
}
