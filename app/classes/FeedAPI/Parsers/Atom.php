<?php
/**
 * ATOM parser.
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
 * Class used to parse ATOM feeds.
 *
 * @category Parsers
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
 */
class Atom extends \FeedAPI\Parser
{
    const PARENT_NODE_NAME = 'feed';
    const MIME_TYPE = 'application/atom+xml';

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
        $data = parent::addHTMLEntities($data);
        $success = @$dom->loadXML($data);

        if (!$success) {
            throw new \FeedAPI\Exception('Invalid Atom data in feed, website ATOM feed is probably broken.');
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

        if(!$dom->getElementsByTagName($nodeName)->item(0)) {
            throw new \Exception('Missing Feed data');
        }

        $feedChildren = $dom->getElementsByTagName($nodeName)->item(0)->childNodes;

        foreach ($feedChildren as $node) {
            $nodeName = strtolower($node->nodeName);

            switch($nodeName) {
            case 'title':
                $result['feed']['title'] = $node->textContent;
                break;
            case 'id':
                if(!isset($result['feed']['site_url'])) {
                    $result['feed']['site_url'] = $node->textContent;
                }
                break;
            case 'link':
                if($node->getAttribute('rel') == 'alternate' && $node->getAttribute('type') == 'text/html') {
                    $result['feed']['site_url'] = $node->getAttribute('href');
                }
                break;
            }
        }

        $entries = $dom->getElementsByTagName('entry');
        for ($e = 0; $e < $entries->length; $e++) {
            $entryChildren = $entries->item($e)->childNodes;
            $item = array();
            foreach ($entryChildren as $node) {
                $nodeName = strtolower($node->nodeName);

                switch($nodeName) {
                case 'title':
                    $item['title'] = $node->textContent;
                    break;
                case 'author':
                    $item['author'] = $node->getElementsByTagName('name')->item(0)->textContent;
                    break;
                case 'content':
                    $item['html'] = $node->textContent;
                    break;
                case 'link':
                    if($node->getAttribute('rel') == 'alternate' && $node->getAttribute('type') == 'text/html') {
                        $item['url'] = $node->getAttribute('href');
                    }
                    break;
                case 'published':
                case 'updated':
                    if(!isset($item['created_on_time']) || $nodeName == 'updated') {
                        $item['created_on_time'] = strtotime($node->textContent);
                    }
                    break;
                case 'id':
                    $item['feed_guid'] = $node->textContent;
                    if(!isset($item['url'])) {
                        $item['url'] = $node->textContent;
                    }
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
