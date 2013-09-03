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
namespace RSSAPI;

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
    private $_data = array();


    /**
     * Response constructor
     *
     * @param  string $api_version Api version (always included in response).
     */
    public function __construct($api_version, $type = 'json')
    {
        $this->_type = ($type === 'xml' ? 'xml' : 'json');
        $this->_data = array(
            'api_version' => $api_version
        );
    }

    /**
     * Set information if authentication succeed.
     *
     * @param  boolean $auth Authentication successful?
     */
    public function setAuth($auth)
    {
        $this->_data['auth'] = ($auth ? '1' : '0');
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
        $groups = \ORM::for_table('groups')->find_array();
        if ($force || !isset($this->_data['groups'])) {
            $this->_data['groups'] = $this->convertIDs($groups);
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
        $feeds_groups = \ORM::for_table('feeds_groups')->find_array();
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
        $feeds = \ORM::for_table('feeds')->find_array();
        if ($force || !isset($this->_data['feeds'])) {
            $this->_data['feeds'] = $this->convertIDs($feeds);
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
        $favicons = \ORM::for_table('favicons')->find_array();
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
        if ($since_id) {
            $items = \ORM::for_table('items')->where_gt('id', $since_id)->limit(50)->find_array();
        } else if ($max_id) {
            // Don't ask, fever API is dumb.
            $items = \ORM::for_table('items')->where_lt('id', $max_id)->order_by_desc('id')->limit(50)->find_array();
            $items = array_reverse($items);
        } else if ($with_ids) {
            $items = \ORM::for_table('items')->where_in('id', $with_ids)->limit(50)->find_array();
        } else {
            $items = \ORM::for_table('items')->limit(50)->find_array();
        }
        if ($force || !isset($this->_data['items'])) {
            $this->_data['total_items'] = (string)count($items);
            $this->_data['items'] = $this->convertIDs($items);
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
        $unread_item_ids = \ORM::for_table('items')->select('id')->where('is_read', 0)->find_array();
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
        $saved_item_ids = \ORM::for_table('items')->select('id')->where('is_saved', 1)->find_array();
        $ids = array();
        foreach ($saved_item_ids as $saved_item_id) {
            $ids[] = $saved_item_id['id'];
        }
        sort($ids, SORT_NUMERIC);
        $this->_data['saved_item_ids'] = implode(',', $ids);
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
}
