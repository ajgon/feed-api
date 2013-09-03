<?php

namespace RSSAPI;

class UnreadItemIdsTest extends \PHPUnit_Framework_TestCase
{
    public function testUnreadItemIdsJSON() {
        $response = new Response(2, 'json');
        $response->includeUnreadItemIds();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['unread_item_ids']);
        $this->assertEquals('1,3,5,6,8,10,11,13,15,16,18,20,21,23,25', $result['unread_item_ids']);
    }

    public function testUnreadItemIdsXML() {
        $response = new Response(2, 'xml');
        $response->includeUnreadItemIds();

        $result = new \DOMDocument();
        $result->loadXML($response->render(true));

        $this->assertEquals(1, $result->getElementsByTagName('unread_item_ids')->length);
        $this->assertEquals('1,3,5,6,8,10,11,13,15,16,18,20,21,23,25', $result->getElementsByTagName('unread_item_ids')->item(0)->childNodes->item(0)->textContent);
    }
}
