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
            return $reponse;
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
            if (is_array($item)) {
                if (isset($item[0])) {
                    $xml .= "<{$key}>";
                    foreach ($item as $child) {
                        $key_singular = preg_replace('/s$/', '', $key);
                        $xml .= $this->generateXML($child, $key_singular);
                    }
                    $xml .= "</{$key}>";
                } elseif (empty($item)) {
                    $xml .= "<{$key}></{$key}>";
                } else {
                    $xml .= $this->generateXML($item, $key);
                }
            } else {
                $xml .= "<{$key}>{$item}</{$key}>";
            }
        }

        $xml .= "</{$node}>";

        return $xml;
    }
}
