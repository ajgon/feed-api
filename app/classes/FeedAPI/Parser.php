<?php
/**
 * Feed parsers supporter.
 *
 * PHP version 5.3
 *
 * @category Core
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
 */
namespace FeedAPI;

/**
 * Abstract Parser class.
 *
 * @category Core
 * @package  FeedAPI
 * @author   Igor Rzegocki <igor@rzegocki.pl>
 * @license  http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 * @link     https://github.com/ajgon/feed-api
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
        case $nodeName == Parsers\RDF::PARENT_NODE_NAME:
            return 'RDF';
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
        case $mimeType == Parsers\RDF::MIME_TYPE:
            return 'RDF';
        }
        return false;
    }

    /**
     * Parses given link for feeds. If url is determined to be HTML file, all feeds data in it are returned. If content is a feed, only this feed information is given back.
     *
     * @param  string $url Item url
     *
     * @return array Feed data in format: [['type' => Atom/RSS/RDF, 'title' => <feed title>, 'url' = > <feed url>], ...]
     */
    static public function fetchFeedData($url) {
        $html = Data::fetch($url);
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $html = self::addHTMLEntities($html);
        $dom->loadXML($html);
        for ($n = 0; $n < $dom->childNodes->length; $n++) {
            $nodeName = $dom->childNodes->item($n)->nodeName;
            if (Parser::detectByNodeName($nodeName)) {
                return array(
                    array(
                        'type' => Parser::detectByNodeName($nodeName),
                        'title' => Parser::detectByNodeName($nodeName) . ' Feed',
                        'url' => $url
                    )
                );
            }
        }

        $dom->loadHTML($html);
        $links = $dom->getElementsByTagName('link');
        $items = array();

        foreach ($links as $link) {
            $rel = $link->getAttribute('rel');
            $type = $link->getAttribute('type');
            if ($rel == 'alternate' && preg_match('/application\/(atom|rss)\+xml/', $type)) {
                // This have to be done this way since alot of sites, says that link type is rss, while serving atom underneath.
                $link_href = $link->getAttribute('href');
                if(!filter_var($link->getAttribute('href'), FILTER_VALIDATE_URL)) {
                    $link_href = trim($url, '/') . '/' . trim($link_href, '/');
                }
                $item = self::fetchFeedData($link_href);
                $item[0]['title'] = $link->getAttribute('title');
                if(!empty($item[0]['url'])) {
                    $items[] = $item[0];
                }
            }
        }

        return $items;
    }

    /**
     * Parses given link for favicon. If url is determined to be HTML file, discovered favicon link is returned. If content is a feed, site url is determined and parsed for favicon url.
     *
     * @param  string $url Item url
     *
     * @return string|boolean Favicon url or false
     */
    static public function fetchFeedFavicon($url) {
        $xml = Data::fetch($url);
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        for ($n = 0; $n < $dom->childNodes->length; $n++) {
            $nodeName = $dom->childNodes->item($n)->nodeName;
            if (Parser::detectByNodeName($nodeName)) {
                $type = Parser::detectByNodeName($nodeName);
                $parserName = '\\FeedAPI\\Parsers\\' . $type;
                $parser = new $parserName();
                $items = $parser->parseLink($url);
                if (!empty($items['feed']['site_url'])) {
                    return self::fetchFeedFavicon($items['feed']['site_url']);
                }
                return false;
            }
        }

        $dom->loadHTML($xml);
        $links = $dom->getElementsByTagName('link');
        $items = array();

        foreach ($links as $link) {
            $rel = $link->getAttribute('rel');
            if (preg_match('/shortcut.?icon/', $rel)) {
                if(!filter_var($link->getAttribute('href'), FILTER_VALIDATE_URL)) {
                    return trim($url, '/') . '/' . trim($link->getAttribute('href'), '/');
                }
                return $link->getAttribute('href');
            }
        }
        return false;
    }

    /**
     * Adds HTML entities to the basic XML file, to properly parse crappy feeds which relies on HTML entities event when they are XML files.
     *
     * @param string $src Source XML
     *
     * @return XML with HTML entities support
     */
    static public function addHTMLEntities($src) {
        // Manually added HTML entities since some crappy blogs uses them in Feeds rather than <![CDATA ]>
        return preg_replace('/\?\>/', "?><!DOCTYPE root [\n<!ENTITY nbsp \"&#160;\"><!ENTITY iexcl \"&#161;\"><!ENTITY cent \"&#162;\"><!ENTITY pound \"&#163;\"><!ENTITY curren \"&#164;\"><!ENTITY yen \"&#165;\"><!ENTITY brvbar \"&#166;\"><!ENTITY sect \"&#167;\"><!ENTITY uml \"&#168;\"><!ENTITY copy \"&#169;\"><!ENTITY ordf \"&#170;\"><!ENTITY laquo \"&#171;\"><!ENTITY not \"&#172;\"><!ENTITY shy \"&#173;\"><!ENTITY reg \"&#174;\"><!ENTITY macr \"&#175;\"><!ENTITY deg \"&#176;\"><!ENTITY plusmn \"&#177;\"><!ENTITY sup2 \"&#178;\"><!ENTITY sup3 \"&#179;\"><!ENTITY acute \"&#180;\"><!ENTITY micro \"&#181;\"><!ENTITY para \"&#182;\"><!ENTITY middot \"&#183;\"><!ENTITY cedil \"&#184;\"><!ENTITY sup1 \"&#185;\"><!ENTITY ordm \"&#186;\"><!ENTITY raquo \"&#187;\"><!ENTITY frac14 \"&#188;\"><!ENTITY frac12 \"&#189;\"><!ENTITY frac34 \"&#190;\"><!ENTITY iquest \"&#191;\"><!ENTITY Agrave \"&#192;\"><!ENTITY Aacute \"&#193;\"><!ENTITY Acirc \"&#194;\"><!ENTITY Atilde \"&#195;\"><!ENTITY Auml \"&#196;\"><!ENTITY Aring \"&#197;\"><!ENTITY AElig \"&#198;\"><!ENTITY Ccedil \"&#199;\"><!ENTITY Egrave \"&#200;\"><!ENTITY Eacute \"&#201;\"><!ENTITY Ecirc \"&#202;\"><!ENTITY Euml \"&#203;\"><!ENTITY Igrave \"&#204;\"><!ENTITY Iacute \"&#205;\"><!ENTITY Icirc \"&#206;\"><!ENTITY Iuml \"&#207;\"><!ENTITY ETH \"&#208;\"><!ENTITY Ntilde \"&#209;\"><!ENTITY Ograve \"&#210;\"><!ENTITY Oacute \"&#211;\"><!ENTITY Ocirc \"&#212;\"><!ENTITY Otilde \"&#213;\"><!ENTITY Ouml \"&#214;\"><!ENTITY times \"&#215;\"><!ENTITY Oslash \"&#216;\"><!ENTITY Ugrave \"&#217;\"><!ENTITY Uacute \"&#218;\"><!ENTITY Ucirc \"&#219;\"><!ENTITY Uuml \"&#220;\"><!ENTITY Yacute \"&#221;\"><!ENTITY THORN \"&#222;\"><!ENTITY szlig \"&#223;\"><!ENTITY agrave \"&#224;\"><!ENTITY aacute \"&#225;\"><!ENTITY acirc \"&#226;\"><!ENTITY atilde \"&#227;\"><!ENTITY auml \"&#228;\"><!ENTITY aring \"&#229;\"><!ENTITY aelig \"&#230;\"><!ENTITY ccedil \"&#231;\"><!ENTITY egrave \"&#232;\"><!ENTITY eacute \"&#233;\"><!ENTITY ecirc \"&#234;\"><!ENTITY euml \"&#235;\"><!ENTITY igrave \"&#236;\"><!ENTITY iacute \"&#237;\"><!ENTITY icirc \"&#238;\"><!ENTITY iuml \"&#239;\"><!ENTITY eth \"&#240;\"><!ENTITY ntilde \"&#241;\"><!ENTITY ograve \"&#242;\"><!ENTITY oacute \"&#243;\"><!ENTITY ocirc \"&#244;\"><!ENTITY otilde \"&#245;\"><!ENTITY ouml \"&#246;\"><!ENTITY divide \"&#247;\"><!ENTITY oslash \"&#248;\"><!ENTITY ugrave \"&#249;\"><!ENTITY uacute \"&#250;\"><!ENTITY ucirc \"&#251;\"><!ENTITY uuml \"&#252;\"><!ENTITY yacute \"&#253;\"><!ENTITY thorn \"&#254;\"><!ENTITY yuml \"&#255;\"><!ENTITY fnof \"&#402;\"><!ENTITY Alpha \"&#913;\"><!ENTITY Beta \"&#914;\"><!ENTITY Gamma \"&#915;\"><!ENTITY Delta \"&#916;\"><!ENTITY Epsilon \"&#917;\"><!ENTITY Zeta \"&#918;\"><!ENTITY Eta \"&#919;\"><!ENTITY Theta \"&#920;\"><!ENTITY Iota \"&#921;\"><!ENTITY Kappa \"&#922;\"><!ENTITY Lambda \"&#923;\"><!ENTITY Mu \"&#924;\"><!ENTITY Nu \"&#925;\"><!ENTITY Xi \"&#926;\"><!ENTITY Omicron \"&#927;\"><!ENTITY Pi \"&#928;\"><!ENTITY Rho \"&#929;\"><!ENTITY Sigma \"&#931;\"><!ENTITY Tau \"&#932;\"><!ENTITY Upsilon \"&#933;\"><!ENTITY Phi \"&#934;\"><!ENTITY Chi \"&#935;\"><!ENTITY Psi \"&#936;\"><!ENTITY Omega \"&#937;\"><!ENTITY alpha \"&#945;\"><!ENTITY beta \"&#946;\"><!ENTITY gamma \"&#947;\"><!ENTITY delta \"&#948;\"><!ENTITY epsilon \"&#949;\"><!ENTITY zeta \"&#950;\"><!ENTITY eta \"&#951;\"><!ENTITY theta \"&#952;\"><!ENTITY iota \"&#953;\"><!ENTITY kappa \"&#954;\"><!ENTITY lambda \"&#955;\"><!ENTITY mu \"&#956;\"><!ENTITY nu \"&#957;\"><!ENTITY xi \"&#958;\"><!ENTITY omicron \"&#959;\"><!ENTITY pi \"&#960;\"><!ENTITY rho \"&#961;\"><!ENTITY sigmaf \"&#962;\"><!ENTITY sigma \"&#963;\"><!ENTITY tau \"&#964;\"><!ENTITY upsilon \"&#965;\"><!ENTITY phi \"&#966;\"><!ENTITY chi \"&#967;\"><!ENTITY psi \"&#968;\"><!ENTITY omega \"&#969;\"><!ENTITY thetasym \"&#977;\"><!ENTITY upsih \"&#978;\"><!ENTITY piv \"&#982;\"><!ENTITY bull \"&#8226;\"><!ENTITY hellip \"&#8230;\"><!ENTITY prime \"&#8242;\"><!ENTITY Prime \"&#8243;\"><!ENTITY oline \"&#8254;\"><!ENTITY frasl \"&#8260;\"><!ENTITY weierp \"&#8472;\"><!ENTITY image \"&#8465;\"><!ENTITY real \"&#8476;\"><!ENTITY trade \"&#8482;\"><!ENTITY alefsym \"&#8501;\"><!ENTITY larr \"&#8592;\"><!ENTITY uarr \"&#8593;\"><!ENTITY rarr \"&#8594;\"><!ENTITY darr \"&#8595;\"><!ENTITY harr \"&#8596;\"><!ENTITY crarr \"&#8629;\"><!ENTITY lArr \"&#8656;\"><!ENTITY uArr \"&#8657;\"><!ENTITY rArr \"&#8658;\"><!ENTITY dArr \"&#8659;\"><!ENTITY hArr \"&#8660;\"><!ENTITY forall \"&#8704;\"><!ENTITY part \"&#8706;\"><!ENTITY exist \"&#8707;\"><!ENTITY empty \"&#8709;\"><!ENTITY nabla \"&#8711;\"><!ENTITY isin \"&#8712;\"><!ENTITY notin \"&#8713;\"><!ENTITY ni \"&#8715;\"><!ENTITY prod \"&#8719;\"><!ENTITY sum \"&#8721;\"><!ENTITY minus \"&#8722;\"><!ENTITY lowast \"&#8727;\"><!ENTITY radic \"&#8730;\"><!ENTITY prop \"&#8733;\"><!ENTITY infin \"&#8734;\"><!ENTITY ang \"&#8736;\"><!ENTITY and \"&#8743;\"><!ENTITY or \"&#8744;\"><!ENTITY cap \"&#8745;\"><!ENTITY cup \"&#8746;\"><!ENTITY int \"&#8747;\"><!ENTITY there4 \"&#8756;\"><!ENTITY sim \"&#8764;\"><!ENTITY cong \"&#8773;\"><!ENTITY asymp \"&#8776;\"><!ENTITY ne \"&#8800;\"><!ENTITY equiv \"&#8801;\"><!ENTITY le \"&#8804;\"><!ENTITY ge \"&#8805;\"><!ENTITY sub \"&#8834;\"><!ENTITY sup \"&#8835;\"><!ENTITY nsub \"&#8836;\"><!ENTITY sube \"&#8838;\"><!ENTITY supe \"&#8839;\"><!ENTITY oplus \"&#8853;\"><!ENTITY otimes \"&#8855;\"><!ENTITY perp \"&#8869;\"><!ENTITY sdot \"&#8901;\"><!ENTITY lceil \"&#8968;\"><!ENTITY rceil \"&#8969;\"><!ENTITY lfloor \"&#8970;\"><!ENTITY rfloor \"&#8971;\"><!ENTITY lang \"&#9001;\"><!ENTITY rang \"&#9002;\"><!ENTITY loz \"&#9674;\"><!ENTITY spades \"&#9824;\"><!ENTITY clubs \"&#9827;\"><!ENTITY hearts \"&#9829;\"><!ENTITY diams \"&#9830;\"><!ENTITY quot \"&#34;\"><!ENTITY amp \"&#38;#38;\"><!ENTITY lt \"&#38;#60;\"><!ENTITY gt \"&#62;\"><!ENTITY apos     \"&#39;\"><!ENTITY OElig \"&#338;\"><!ENTITY oelig \"&#339;\"><!ENTITY Scaron \"&#352;\"><!ENTITY scaron \"&#353;\"><!ENTITY Yuml \"&#376;\"><!ENTITY circ \"&#710;\"><!ENTITY tilde \"&#732;\"><!ENTITY ensp \"&#8194;\"><!ENTITY emsp \"&#8195;\"><!ENTITY thinsp \"&#8201;\"><!ENTITY zwnj \"&#8204;\"><!ENTITY zwj \"&#8205;\"><!ENTITY lrm \"&#8206;\"><!ENTITY rlm \"&#8207;\"><!ENTITY ndash \"&#8211;\"><!ENTITY mdash \"&#8212;\"><!ENTITY lsquo \"&#8216;\"><!ENTITY rsquo \"&#8217;\"><!ENTITY sbquo \"&#8218;\"><!ENTITY ldquo \"&#8220;\"><!ENTITY rdquo \"&#8221;\"><!ENTITY bdquo \"&#8222;\"><!ENTITY dagger \"&#8224;\"><!ENTITY Dagger \"&#8225;\"><!ENTITY permil \"&#8240;\"><!ENTITY lsaquo \"&#8249;\"><!ENTITY rsaquo \"&#8250;\"><!ENTITY euro \"&#8364;\">\n]>", $src, 1);
    }
}
