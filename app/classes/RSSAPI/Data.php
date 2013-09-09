<?php
/**
 * Data-related methods.
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
 * Helper class with static methods to do different data manipulation tasks.
 *
 * @category Core
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class Data
{
    /**
     * Fetch data from given URL.
     *
     * @param  string $url URL
     *
     * @return string Raw data response
     */
    public static function fetch($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if (!empty($error)) {
            throw new Exception("Error while performing request: {$error}.");
        }

        return $result;
    }

    /**
     * Puts array of feed/items data to database.
     *
     * @param array $items Format: array( 'feed' => array( feeds_table_column_names => feeds_table_column_values ), 'items' => array( items_table_column_names => items_table_column_values ) )
     *
     * @return null
     */
    public static function addToDatabase($items)
    {
        $base = new Base();
        $base->initDatabase();

        $feed = \ORM::for_table('feeds')->where('url', $items['feed']['url'])->find_one();

        // Add feed
        if (isset($items['feed']) && !empty($items['feed']['url']) && !$feed) {
            $feed = \ORM::for_table('feeds')->create();
            foreach ($items['feed'] as $key => $value) {
                $feed->set($key, $value);
            }
            $feed->save();
        }

        if(isset($items['items'])) {
            foreach ($items['items'] as $data) {
                $item = \ORM::for_table('items')->where('rss_id', $data['rss_id'])->find_one();
                if(!$item) {
                    $item = \ORM::for_table('items')->create();
                }
                if(!$item->created_on_time || $item->created_on_time < $data['created_on_time']) {
                    foreach ($data as $key => $value) {
                        $item->set($key, $value);
                    }
                    $item->feed_id = $feed->id;
                    $item->save();
                }
            }
        }

        if(isset($items['group'])) {
            $group = \ORM::for_table('groups')->create();
            $group->title = $items['group']['title'];
            $group->save();
        }

        if(isset($items['user'])) {
            $user = \ORM::for_table('users')->create();
            $user->email = $items['user']['email'];
            $user->api_key = $items['user']['api_key'];
            $user->save();
        }
    }
}
