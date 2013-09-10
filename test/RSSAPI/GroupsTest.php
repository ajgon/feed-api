<?php

namespace RSSAPI;

class GroupsTest extends \PHPUnit_Framework_TestCase
{
    public function testGroupsJSON() {
        $response = new Response(2, 'json');
        $response->setUser('86b175152449a29e2c217c90965659d8');
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
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeGroups();

        $result = new \DOMDocument();
        $result->loadXML($response->render(true));

        $this->assertEquals(1, $result->getElementsByTagName('groups')->length);
        $this->assertEquals(5, $result->getElementsByTagName('group')->length);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals('Test Group #' . ($i + 1), $result->getElementsByTagName('group')->item($i)->childNodes->item(1)->textContent);
        }
    }

    public function testACLGroupsJSON() {
        $response = new Response(2, 'json');
        $response->setUser('105404aef1fb9f9952e8433294fe44a8');
        $response->includeGroups();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['groups']);
        $this->assertCount(4, $result['groups']);
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals('Test Group #' . ($i > 0 ? $i + 2 : $i + 1), $result['groups'][$i]['title']);
        }
    }

    public function testWriteGroups() {
        $response = new Response(2, 'json');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $ids = array(10, 11, 12, 13, 14, 20, 21);

        $response->mark('group', 'read', 3, 1000000053);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        foreach ($ids as $i) {
            $this->assertEquals(1, $result['items'][$i]['is_read']);
        }
        $this->assertEquals(0, $result['items'][15]['is_read']);
        $this->assertEquals(1, $result['items'][16]['is_read']);
        $this->assertEquals(0, $result['items'][17]['is_read']);
        $this->assertEquals(1, $result['items'][18]['is_read']);
        $this->assertEquals(0, $result['items'][19]['is_read']);

        $response->mark('group', 'unread', 3, 1000000053);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        foreach ($ids as $i) {
            $this->assertEquals(0, $result['items'][$i]['is_read']);
        }
        $this->assertEquals(0, $result['items'][15]['is_read']);
        $this->assertEquals(1, $result['items'][16]['is_read']);
        $this->assertEquals(0, $result['items'][17]['is_read']);
        $this->assertEquals(1, $result['items'][18]['is_read']);
        $this->assertEquals(0, $result['items'][19]['is_read']);

        $response->mark('group', 'saved', 3, 1000000053);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        foreach ($ids as $i) {
            $this->assertEquals(1, $result['items'][$i]['is_saved']);
        }
        $this->assertEquals(0, $result['items'][15]['is_saved']);
        $this->assertEquals(0, $result['items'][16]['is_saved']);
        $this->assertEquals(1, $result['items'][17]['is_saved']);
        $this->assertEquals(1, $result['items'][18]['is_saved']);
        $this->assertEquals(0, $result['items'][19]['is_saved']);

        $response->mark('group', 'unsaved', 3, 1000000053);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        foreach ($ids as $i) {
            $this->assertEquals(0, $result['items'][$i]['is_saved']);
        }
        $this->assertEquals(0, $result['items'][15]['is_saved']);
        $this->assertEquals(0, $result['items'][16]['is_saved']);
        $this->assertEquals(1, $result['items'][17]['is_saved']);
        $this->assertEquals(1, $result['items'][18]['is_saved']);
        $this->assertEquals(0, $result['items'][19]['is_saved']);

        $response->mark('item', 'read', 12);
        $response->mark('item', 'read', 14);
        $response->mark('item', 'read', 22);
        $response->mark('item', 'saved', 13);
        $response->mark('item', 'saved', 14);
    }
}
