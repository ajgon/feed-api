<?php
/**
 * RDF parser.
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
 * Class used to parse RDF feeds.
 *
 * @category Parsers
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
 */
class RDF extends RSS
{
    const PARENT_NODE_NAME = 'rdf:RDF';
    const MIME_TYPE = 'application/rss+xml';
}
