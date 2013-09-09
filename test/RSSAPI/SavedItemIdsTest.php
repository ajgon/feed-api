<?php

namespace RSSAPI;

class SavedItemIdsTest extends \PHPUnit_Framework_TestCase
{
    public function testSavedItemIdsJSON() {
        $response = new Response(2, 'json');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeSavedItemIds();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['saved_item_ids']);
        $this->assertEquals('3,4,8,9,13,14,18,19,23,24', $result['saved_item_ids']);
    }

    public function testSavedItemIdsXML() {
        $response = new Response(2, 'xml');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeSavedItemIds();

        $result = new \DOMDocument();
        $result->loadXML($response->render(true));

        $this->assertEquals(1, $result->getElementsByTagName('saved_item_ids')->length);
        $this->assertEquals('3,4,8,9,13,14,18,19,23,24', $result->getElementsByTagName('saved_item_ids')->item(0)->childNodes->item(0)->textContent);
    }
}
