<?php
/**
 * RSS parser.
 *
 * PHP version 5.3
 *
 * @category Parsers
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
 */
namespace FeedAPI\Parsers;

/**
 * Class used to parse RSS feeds.
 *
 * @category Parsers
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
 */
class RSS extends \FeedAPI\Parser
{
    const PARENT_NODE_NAME = 'rss';
    const MIME_TYPE = 'application/rss+xml';

    /**
     * Fetches feed data from given link, and returns them in array('feed' => ..., 'items' => ...) format.
     *
     * @param  string $url FeedURL
     *
     * @return array ['feed' => ..., 'items' => ...] format.
     */
    public function parseLink($url) {
        $result = $this->parseData(\FeedAPI\Data::fetch($url));
        $result['feed']['url'] = $url;

        return $result;
    }

    /**
     * Converts feeds XML to array('feed' => ..., 'items' => ...) format.
     *
     * @param  string $url Feed XML
     *
     * @return array ['feed' => ..., 'items' => ...] format.
     */
    public function parseData($data) {
        $dom = new \DOMDocument();
        $success = @$dom->loadXML($data);

        if (!$success) {
            throw new \FeedAPI\Exception('Invalid RSS data in feed, website RSS feed is probably broken.');
        }
        $time = time();

        $result = array(
            'feed' => array(
                'last_updated_on_time' => $time,
                'feed_type' => preg_replace('/^.*\\\\/', '', get_class($this))
            ),
            'items' => array()
        );

        $self = get_class($this);
        $nodeName = preg_replace('/^.*:/', '', $self::PARENT_NODE_NAME); // strip namespaces

        $feedChildren = $dom->getElementsByTagName($nodeName)->item(0)->getElementsByTagName('channel')->item(0)->childNodes;

        foreach ($feedChildren as $node) {
            $nodeName = strtolower($node->nodeName);

            switch($nodeName) {
            case 'title':
                $result['feed']['title'] = $node->textContent;
                break;
            case 'link':
                $result['feed']['site_url'] = $node->textContent;
                break;
            }
        }

        $items = $dom->getElementsByTagName('item');
        for ($i = 0; $i < $items->length; $i++) {
            $itemChildren = $items->item($i)->childNodes;
            $item = array('author' => '');
            foreach ($itemChildren as $node) {
                $nodeName = strtolower($node->nodeName);

                switch($nodeName) {
                case 'title':
                    $item['title'] = $node->textContent;
                    break;
                case 'creator':
                case 'dc:creator':
                    $item['author'] = $node->textContent;
                    break;
                case 'description':
                case 'content':
                case 'content:encoded':
                    $item['html'] = $node->textContent;
                    break;
                case 'link':
                    $item['url'] = $node->textContent;
                    break;
                case 'pubdate':
                case 'dc:date':
                    $item['created_on_time'] = strtotime($node->textContent);
                    break;
                case 'guid':
                    $item['feed_guid'] = $node->textContent;
                    break;
                }
            }
            if(!isset($item['feed_guid'])) {
                $item['feed_guid'] = sha1(serialize(($item)));
            }
            $item['added_on_time'] = $time;
            $result['items'][] = $item;
        }

        return $result;
    }
}
