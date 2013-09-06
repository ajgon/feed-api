<?php
/**
 * Feed parsers supporter.
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
 * Abstract Parser class.
 *
 * @category Core
 * @package  RSS-API
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/rss-api
 */
abstract class Parser
{
    /**
     * Fetches feed data from given link, and returns them in array('feed' => ..., 'items' => ...) format.
     *
     * @param  string $url FeedURL
     *
     * @abstract
     * @return array ['feed' => ..., 'items' => ...] format.
     */
    abstract public function parseLink($url);

    /**
     * Converts feeds XML to array('feed' => ..., 'items' => ...) format.
     *
     * @param  string $url Feed XML
     *
     * @abstract
     * @return array ['feed' => ..., 'items' => ...] format.
     */
    abstract public function parseData($data);

    /**
     * Determines feed type by parent node name.
     *
     * @param  string $nodeName Parent node name
     *
     * @return string|boolean Feed type or false
     */
    static public function detectByNodeName($nodeName) {
        switch(true) {
        case $nodeName == Parsers\Atom::PARENT_NODE_NAME:
            return 'Atom';
        case $nodeName == Parsers\RSS::PARENT_NODE_NAME:
            return 'RSS';
        }
        return false;
    }

    /**
     * Determines feed type by mime type.
     *
     * @param  string $nodeName Mime type
     *
     * @return string|boolean Feed type or false
     */
    static public function detectByMimeType($mimeType) {
        switch(true) {
        case $mimeType == Parsers\Atom::MIME_TYPE:
            return 'Atom';
        case $mimeType == Parsers\RSS::MIME_TYPE:
            return 'RSS';
        }
        return false;
    }

    /**
     * Parses given link for feeds. If url is determined to be HTML file, all feeds data in it are returned. If content is a feed, only this feed information is given back.
     *
     * @param  string $url Item url
     *
     * @return array Feed data in format: ['type' => Atom/RSS, 'title' => <feed title>, 'url' = > <feed url>]
     */
    static public function fetchFeedLink($url) {
        $html = Data::fetch($url);
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($html);
        for ($n = 0; $n < $dom->childNodes->length; $n++) {
            $nodeName = strtolower($dom->childNodes->item($n)->nodeName);
            if (Parser::detectByNodeName($nodeName)) {
                return array(
                    'type' => Parser::detectByNodeName($nodeName),
                    'title' => Parser::detectByNodeName($nodeName) . ' Feed',
                    'url' => $url
                );
            }
        }

        $dom->loadHTML($html);
        $links = $dom->getElementsByTagName('link');
        $items = array();
        $list = array();

        foreach ($links as $link) {
            $rel = $link->getAttribute('rel');
            $type = $link->getAttribute('type');
            if ($rel == 'alternate' && preg_match('/application\/(atom|rss)\+xml/', $type)) {
                // This have to be done this way since alot of sites, says that link type is rss, while serving atom underneath.
                $items[] = self::fetchFeedLink($link->getAttribute('href'));
            }
        }

        return $items;
    }
}
