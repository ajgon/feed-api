<?php

namespace RSSAPI;

class FaviconsTest extends \PHPUnit_Framework_TestCase
{
    public function testFaviconsJSON() {
        $response = new Response(2, 'json');
        $response->includeFavicons();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['favicons']);
        $this->assertCount(5, $result['favicons']);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals('data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', $result['favicons'][$i]['data']);
        }
    }

    public function testFaviconsXML() {
        $response = new Response(2, 'xml');
        $response->includeFavicons();

        $result = new \DOMDocument();
        $result->loadXML($response->render(true));

        $this->assertEquals(1, $result->getElementsByTagName('favicons')->length);
        $this->assertEquals(5, $result->getElementsByTagName('favicon')->length);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals('data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', $result->getElementsByTagName('favicon')->item($i)->childNodes->item(1)->textContent);
        }
    }
}
