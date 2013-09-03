<?php

namespace RSSAPI;

class GroupsTest extends \PHPUnit_Framework_TestCase
{
    public function testGroupsJSON() {
        $response = new Response(2, 'json');
        $response->includeGroups();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['groups']);
        $this->assertCount(5, $result['groups']);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals('Test Group #' . ($i + 1), $result['groups'][$i]['title']);
        }
    }

    public function testGroupsXML() {
        $response = new Response(2, 'xml');
        $response->includeGroups();

        $result = new \DOMDocument();
        $result->loadXML($response->render(true));

        $this->assertEquals(1, $result->getElementsByTagName('groups')->length);
        $this->assertEquals(5, $result->getElementsByTagName('group')->length);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals('Test Group #' . ($i + 1), $result->getElementsByTagName('group')->item($i)->childNodes->item(1)->textContent);
        }
    }
}
