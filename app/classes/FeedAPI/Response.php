<?php
/**
 * Response class file.
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
 * Class used to prepare response from RSS-API.
 *
 * @category Core
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class Response
{
    private $_type = array();
    private $_data = array();
    private $_user = array();

    /**
     * Response constructor
     *
     * @param  string $api_version Api version (always included in response).
     */
    public function __construct($api_version, $type = 'json')
    {
        $this->_type = ($type === 'xml' ? 'xml' : 'json');
        $this->_data = array(
            'api_version' => (int)$api_version
        );
    }

    /**
     * Set information if authentication succeed.
     *
     * @param  boolean $auth Authentication successful?
     */
    public function setAuth($auth)
    {
        $this->_data['auth'] = ($auth ? 1 : 0);
    }

    /**
     * Set information about user
     *
     * @param ORM|string $user User data or api key
     */
    public function setUser($user) {
        if($user instanceof ORM) {
            $this->_user = $user;
        } else {
            $user = \ORM::for_table('users')->where('api_key', $user)->find_one();
            if($user) {
                $this->_user = $user;
            } else {
                throw new Exception('Invalid API Key');
            }
        }

    }

    /**
     * Include information about last API call in response for given user (identified via API Key).
     *
     * @param  string $api_key API Key
     *
     * @return null
     */
    public function includeLastRefreshsedOnTime()
    {
        $this->_data['last_refreshed_on_time'] = $this->_user->last_refreshed_on_time;
        $this->_user->last_refreshed_on_time = time();
        $this->_user->save();
    }

    /**
     * Include groups in response
     *
     * @param  boolean $force Force replace (normally, when groups are included, they won't be included again)
     *
     * @return null
     */
    public function includeGroups($force = false)
    {
        $feed_ids = $this->collectUserFeedIDs();

        $groups = \ORM::for_table('groups')->join('feeds_groups', array('groups.id', '=', 'feeds_groups.group_id'))->where_in('feeds_groups.feed_id', $feed_ids)->group_by('groups.id')->find_array();
        if ($force || !isset($this->_data['groups'])) {
            $this->_data['groups'] = $this->convertIDs($this->stripFields($groups, array('feed_id', 'group_id')));
        }
    }

    /**
     * Include feeds_groups in response
     *
     * @param  boolean $force Force replace (normally, when feeds_groups are included, they won't be included again)
     *
     * @return null
     */
    public function includeFeedsGroups($force = false)
    {
        $feed_ids = $this->collectUserFeedIDs();

        $feeds_groups = \ORM::for_table('feeds_groups')->where_in('feed_id', $feed_ids)->order_by_asc('group_id')->find_array();
        if ($force || !isset($this->_data['feeds_groups'])) {
            $map_feeds_groups = array();
            $result = array();
            foreach ($feeds_groups as $item) {
                $map_feeds_groups[$item['group_id']][] = $item['feed_id'];
            }
            foreach ($map_feeds_groups as $group_id => $feed_ids) {
                sort($feed_ids, SORT_NUMERIC);
                $result[] = array(
                    'group_id' => (int)$group_id,
                    'feed_ids' => implode(',', $feed_ids)
                );
            }
            $this->_data['feeds_groups'] = $result;
        }
    }

    /**
     * Include feeds in response
     *
     * @param  boolean $force Force replace (normally, when feeds are included, they won't be included again)
     *
     * @return null
     */
    public function includeFeeds($force = false)
    {
        $feeds = $this->collectUserFeeds();
        if ($force || !isset($this->_data['feeds'])) {
            $this->_data['feeds'] = $this->convertIDs($this->stripFields($feeds, 'feed_type'));
        }
    }

    /**
     * Include favicons in response
     *
     * @param  boolean $force Force replace (normally, when favicons are included, they won't be included again)
     *
     * @return null
     */
    public function includeFavicons($force = false)
    {
        $feeds = $this->collectUserFeeds();
        $favicons_ids = array();
        foreach ($feeds as $feed) {
            $favicons_ids[] = $feed['favicon_id'];
        }

        $favicons = \ORM::for_table('favicons')->where_in('id', $favicons_ids)->find_array();
        if ($force || !isset($this->_data['favicons'])) {
            $this->_data['favicons'] = $this->convertIDs($favicons);
        }
    }

    /**
     * Include items in response
     *
     * @param  boolean $force Force replace (normally, when items are included, they won't be included again)
     *
     * @return null
     */
    public function includeItems($force = false, $since_id = null, $max_id = null, $with_ids = null)
    {
        $feed_ids = $this->collectUserFeedIDs();

        if ($since_id) {
            $items = \ORM::for_table('items')->where_in('feed_id', $feed_ids)->where_gt('id', $since_id)->limit(50)->find_array();
        } else if ($max_id) {
            // Don't ask, fever API is dumb.
            $items = \ORM::for_table('items')->where_in('feed_id', $feed_ids)->where_lt('id', $max_id)->order_by_desc('id')->limit(50)->find_array();
            //$items = array_reverse($items);
        } else if ($with_ids) {
            $items = \ORM::for_table('items')->where_in('feed_id', $feed_ids)->where_in('id', $with_ids)->limit(50)->find_array();
        } else {
            $items = \ORM::for_table('items')->where_in('feed_id', $feed_ids)->limit(50)->find_array();
        }
        if ($force || !isset($this->_data['items'])) {
            $this->_data['total_items'] = (string)\ORM::for_table('items')->where_in('feed_id', $feed_ids)->count();
            $this->_data['items'] = $this->convertIDs($this->stripFields($items, array('added_on_time', 'rss_id')));
        }
    }

    /**
     * Include links in response (dummy function, not implemented, includes empty array)
     *
     * @param  boolean $force Force replace (dummy parameter)
     *
     * @return null
     */
    public function includeLinks($force = false)
    {
        // Not implemented, just dummy function
        $this->_data['links'] = array();
    }

    /**
     * Include unread item ids in response
     *
     * @param  boolean $force Force replace (normally, when ids are included, they won't be included again)
     *
     * @return null
     */
    public function includeUnreadItemIds($force = false) {
        $feed_ids = $this->collectUserFeedIDs();

        $unread_item_ids = \ORM::for_table('items')->select('id')->where_in('feed_id', $feed_ids)->where('is_read', 0)->find_array();
        $ids = array();
        foreach ($unread_item_ids as $unread_item_id) {
            $ids[] = $unread_item_id['id'];
        }
        sort($ids, SORT_NUMERIC);
        $this->_data['unread_item_ids'] = implode(',', $ids);
    }


    /**
     * Include saved item ids in response
     *
     * @param  boolean $force Force replace (normally, when ids are included, they won't be included again)
     *
     * @return null
     */
    public function includeSavedItemIds($force = false) {
        $feed_ids = $this->collectUserFeedIDs();

        $saved_item_ids = \ORM::for_table('items')->select('id')->where_in('feed_id', $feed_ids)->where('is_saved', 1)->find_array();
        $ids = array();
        foreach ($saved_item_ids as $saved_item_id) {
            $ids[] = $saved_item_id['id'];
        }
        sort($ids, SORT_NUMERIC);
        $this->_data['saved_item_ids'] = implode(',', $ids);
    }

    /**
     * Write API in one method. It covers all actions for setting RSS items/feeds/groups as read/unread or saved/unsaved.
     *
     * @param  string    $type   Object type [item|feed|group]
     * @param  string    $as     Object status [read|unread|saved|unsaved]
     * @param  string    $id     Object id
     * @param  timestamp $before Only for feed/group. Include only items for give feed/group that are older than timestamp specified in this parameter.
     *
     * @return null
     */
    public function mark($type, $as, $id, $before = null) {
        $type = strtolower($type);
        $as = strtolower($as);
        $id = (int)$id;
        $before = (int)$before;
        if (($as === 'read' || $as === 'unread' || $as === 'saved' || $as === 'unsaved') && $id > 0) {
            $field = ($as === 'read' || $as === 'unread') ? 'is_read' : 'is_saved';
            $value = ($as === 'read' || $as === 'saved') ? 1 : 0;

            if ($type === 'item') {
                $record = \ORM::for_table('items')->find_one($id);
                $record->set($field, $value);
                $record->save();
            }

            if ($type === 'feed') {
                $records = \ORM::for_table('items')->select('id')->where('feed_id', $id)->where_lt('items.added_on_time', $before)->find_array();

                // Has to be done since idiorm is to basic to do multiple updates
                $ids = array();
                foreach ($records as $record) {
                    $ids[] = $record['id'];
                }
                if(!empty($ids)) {
                    \ORM::for_table('items')->raw_query('UPDATE items SET `' . $field . '` = :field WHERE `id` IN (' . implode(',', $ids) . ')', array('field' => $value))->find_one();
                }
            }

            if ($type === 'group') {
                $feeds_groups = \ORM::for_table('feeds_groups')->where('group_id', $id)->find_array();
                $feeds = array();
                foreach ($feeds_groups as $fg) {
                    $feeds[] = $fg['feed_id'];
                }
                $records = \ORM::for_table('items')->where_in('feed_id', $feeds)->where_lt('items.added_on_time', $before)->find_array();

                // Has to be done since idiorm is to basic to do multiple updates
                $ids = array();
                foreach ($records as $record) {
                    $ids[] = $record['id'];
                }
                if(!empty($ids)) {
                    \ORM::for_table('items')->raw_query('UPDATE items SET `' . $field . '` = :field WHERE `id` IN (' . implode(',', $ids) . ')', array('field' => $value))->find_one();
                }
            }
        }
    }

    /**
     * Renders response
     *
     * @param  boolean $return Should response be returned (true) or displayed (false).
     *
     * @return string|null Response if $return == true
     */
    public function render($return = false)
    {
        if ($this->_type === 'json') {
            $response = $this->generateJSON($this->_data);
        } else {
            $response = '<?xml version="1.0" encoding="utf-8"?>';
            $response .= $this->generateXML($this->_data);
        }

        if ($return) {
            return $response;
        } else {
            header('Content-Type: application/json');
            echo $response;
        }
    }

    /**
     * Converts given array to JSON.
     *
     * @param  array  $data Given array
     *
     * @return string JSON converted array
     */
    private function generateJSON($data)
    {
        return json_encode($data);
    }

    /**
     * Converts given array to XML.
     *
     * @param  array  $data Given array
     *
     * @return string XML converted array
     */
    private function generateXML($data, $node = 'response')
    {
        $xml = "<{$node}>";

        foreach ($data as $key => $item) {
            $cdata_pre = $key === 'html' ? '<![CDATA[' : '';
            $cdata_suf = $key === 'html' ? ']]>' : '';
            if (is_array($item)) {
                if (isset($item[0])) {
                    $xml .= "<{$key}>{$cdata_pre}";
                    foreach ($item as $child) {
                        $key_singular = preg_replace('/s$/', '', $key);
                        $xml .= $this->generateXML($child, $key_singular);
                    }
                    $xml .= "{$cdata_suf}</{$key}>";
                } elseif (empty($item)) {
                    $xml .= "<{$key}></{$key}>";
                } else {
                    $xml .= $this->generateXML($item, $key);
                }
            } else {
                $xml .= "<{$key}>{$cdata_pre}{$item}{$cdata_suf}</{$key}>";
            }
        }

        $xml .= "</{$node}>";

        return $xml;
    }

    /**
     * Returns all feeds attached to specific user.
     *
     * @return array Feeds array
     */
    private function collectUserFeeds() {
        if($this->_user->super) {
            $feeds = \ORM::for_table('feeds')->find_array();
        } else {
            $feeds = \ORM::for_table('feeds')->join('feeds_users', array('feeds.id', '=', 'feeds_users.feed_id'))->where('feeds_users.user_id', $this->_user->id)->find_array();
        }
        return $feeds;
    }

    /**
     * Returns all feed IDs attached to specific user.
     *
     * @return array Feed IDs array
     */
    private function collectUserFeedIDs() {
        $feeds = $this->collectUserFeeds();
        $ids = array();
        foreach ($feeds as $feed) {
            $ids[] = $feed['id'];
        }
        return $ids;
    }

    /**
     * Converts all fields in given array with name ending in *id to integer.
     *
     * @param  array $items Given items
     *
     * @return array Items with ids converted to integer.
     */
    private function convertIDs($items) {
        foreach ($items as $i => $item) {
            foreach ($item as $key => $value) {
                if (preg_match('/_?id$/', $key)) {
                    $items[$i][$key] = (int)$value;
                }
            }
        }
        return $items;
    }

    /**
     * Removes keys from $items array which are match $fields contents.
     *
     * @param  array $items  Give array
     * @param  array $fields Array of fields which should be removed
     *
     * @return array Stripped array
     */
    private function stripFields($items, $fields) {
        $result = array();
        $fields = is_array($fields) ? $fields : array($fields);
        foreach ($items as $item) {
            foreach ($fields as $field) {
                unset($item[$field]);
            }
            $result[] = $item;
        }
        return $result;
    }
}
