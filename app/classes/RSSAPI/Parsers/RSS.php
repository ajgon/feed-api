<?php
/**
 * RSS parser.
 *
 * PHP version 5.3
 *
 * @category Parsers
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
namespace RSSAPI\Parsers;

/**
 * Class used to parse RSS feeds.
 *
 * @category Parsers
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
class RSS extends \RSSAPI\Parser
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
        $result = $this->parseData(\RSSAPI\Data::fetch($url));
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
        $success = $dom->loadXML($data);

        if (!$success) {
            throw new \RSSAPI\Exception('Invalid RSS data in feed, website RSS feed is probably broken.');
        }
        $time = time();

        $result = array(
            'feed' => array(
                'last_updated_on_time' => $time,
                'feed_type' => 'RSS'
            ),
            'items' => array()
        );

        $feedChildren = $dom->getElementsByTagName('rss')->item(0)->getElementsByTagName('channel')->item(0)->childNodes;

        foreach ($feedChildren as $node) {
            $nodeName = strtolower($node->tagName);

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
                $nodeName = strtolower($node->tagName);

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
                    $item['created_on_time'] = strtotime($node->textContent);
                    break;
                case 'guid':
                    $item['rss_id'] = $node->textContent;
                    break;
                }
            }
            $item['added_on_time'] = $time;
            $result['items'][] = $item;
        }

        return $result;
    }
}
