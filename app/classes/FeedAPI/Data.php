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
namespace FeedAPI;

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
     * @param array   $items Format: array( 'feed' => array( feeds_table_column_names => feeds_table_column_values ), 'items' => array( items_table_column_names => items_table_column_values ) )
     * @param boolean $force Force insertion
     * @return array IDs of created items
     */
    public static function addToDatabase($items, $force = false)
    {
        $base = new Base();
        $base->initDatabase();

        $result = array();

        // Add feed
        if (isset($items['feed'])) {
            $feed = \ORM::for_table('feeds')->where('url', $items['feed']['url'])->find_one();
            if(!empty($items['feed']['url']) && ($force || !$feed)) {
                if(!$feed) {
                    $feed = \ORM::for_table('feeds')->create();
                }

                foreach ($items['feed'] as $key => $value) {
                    $feed->set($key, $value);
                }
                $feed->save();
                $result['feed'] = $feed->id;
            } else {
                throw new \FeedAPI\Exception('Feed already exists or missing url!');
            }
        }

        if(isset($items['items'])) {
            $result['items'] = array();
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
                    $result['items'][] = $item->id;
                }
            }
        }

        if(isset($items['group'])) {
            $group = \ORM::for_table('groups')->create();
            $group->title = $items['group']['title'];
            $group->save();
            $result['group'] = $group->id;
        }

        if(isset($items['user'])) {
            $user = \ORM::for_table('users')->create();
            $user->email = $items['user']['email'];
            $user->api_key = $items['user']['api_key'];
            $user->super = isset($items['user']['super']) ? $items['user']['super'] : 0;
            $user->save();
            $result['user'] = $user->id;
        }

        if(isset($items['favicon'])) {
            $favicon = \ORM::for_table('favicons')->create();
            $favicon->data = $items['favicon']['data'];
            $favicon->save();
            $result['favicon'] = $favicon->id;
        }

        return $result;
    }
}
