<?php

namespace FeedAPI;

class ItemsTest extends \PHPUnit_Framework_TestCase
{
    public function testReadItemsJSON() {
        $response = new Response(2, 'json');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeItems();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['items']);
        $this->assertEquals(25, $result['total_items']);
        $this->assertCount(25, $result['items']);
        for ($i = 1; $i < 6; $i++) {
            for ($j = 1; $j < 6; $j++) {
                $idx = ($i - 1) * 5 + $j - 1;
                $this->assertEquals($i, $result['items'][$idx]['feed_id']);
                $this->assertArrayNotHasKey('feed_guid', $result['items'][$idx]);
                $this->assertEquals("Item {$i}.{$j}", $result['items'][$idx]['title']);
                $this->assertEquals("Author {$i}.{$j}", $result['items'][$idx]['author']);
                $this->assertEquals("<div class=\"entry\">{$i}.{$j}</div>", $result['items'][$idx]['html']);
                $this->assertEquals("http://example.com/item{$i}.{$j}", $result['items'][$idx]['url']);
                $this->assertEquals($j == 3 || $j == 4 ? 1 : 0, $result['items'][$idx]['is_saved']);
                $this->assertEquals($j == 2 || $j == 4 ? 1 : 0, $result['items'][$idx]['is_read']);
                $this->assertEquals(1000000000 + 10 * $i + $j, $result['items'][$idx]['created_on_time']);
                $this->assertArrayNotHasKey('added_on_time', $result['items'][$idx]);
            }
        }

        // since_id
        $response->includeItems(true, 8);
        $result = json_decode($response->render(true), true);
        $this->assertNotEmpty($result['items']);
        $this->assertEquals(25, $result['total_items']);
        $this->assertCount(17, $result['items']);

        // max_id
        $response->includeItems(true, null, 13);
        $result = json_decode($response->render(true), true);
        $this->assertNotEmpty($result['items']);
        $this->assertEquals(25, $result['total_items']);
        $this->assertCount(12, $result['items']);

        // with_ids
        $ids = array(3, 8, 11, 17, 22, 25);
        $response->includeItems(true, null, null, $ids);
        $result = json_decode($response->render(true), true);
        $this->assertNotEmpty($result['items']);
        $this->assertEquals(25, $result['total_items']);
        $this->assertCount(6, $result['items']);
        foreach($result['items'] as $r => $res) {
            $this->assertEquals($ids[$r], $res['id']);
        }
    }

    public function testReadItemsXML() {
        $response = new Response(2, 'xml');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeItems();

        $result = new \DOMDocument();
        $result->loadXML($response->render(true));

        $this->assertEquals(1, $result->getElementsByTagName('items')->length);
        $this->assertEquals(25, $result->getElementsByTagName('total_items')->item(0)->textContent);
        $this->assertEquals(25, $result->getElementsByTagName('item')->length);
        for ($i = 1; $i < 6; $i++) {
            for($j = 1; $j < 6; $j++) {
                $idx = ($i - 1) * 5 + $j - 1;
                $this->assertEquals($i, $result->getElementsByTagName('item')->item($idx)->childNodes->item(1)->textContent);
                $this->assertEquals("Item {$i}.{$j}", $result->getElementsByTagName('item')->item($idx)->childNodes->item(2)->textContent);
                $this->assertEquals("Author {$i}.{$j}", $result->getElementsByTagName('item')->item($idx)->childNodes->item(3)->textContent);
                $this->assertEquals("<div class=\"entry\">{$i}.{$j}</div>", $result->getElementsByTagName('item')->item($idx)->childNodes->item(4)->textContent);
                $this->assertEquals("http://example.com/item{$i}.{$j}", $result->getElementsByTagName('item')->item($idx)->childNodes->item(5)->textContent);
                $this->assertEquals($j == 3 || $j == 4 ? 1 : 0, $result->getElementsByTagName('item')->item($idx)->childNodes->item(6)->textContent);
                $this->assertEquals($j == 2 || $j == 4 ? 1 : 0, $result->getElementsByTagName('item')->item($idx)->childNodes->item(7)->textContent);
                $this->assertEquals(1000000000 + 10 * $i + $j, $result->getElementsByTagName('item')->item($idx)->childNodes->item(8)->textContent);
            }
        }
    }

    public function testACLReadItemsJSON() {
        $response = new Response(2, 'json');
        $response->setUser('105404aef1fb9f9952e8433294fe44a8');
        $response->includeItems();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['items']);
        $this->assertEquals(15, $result['total_items']);
        $this->assertCount(15, $result['items']);
        for ($i = 1; $i < 4; $i++) {
            for ($j = 1; $j < 6; $j++) {
                $w = ($i - 1) * 2 + 1;
                $idx = ($i - 1) * 5 + $j - 1;
                $this->assertEquals($w, $result['items'][$idx]['feed_id']);
                $this->assertArrayNotHasKey('feed_guid', $result['items'][$idx]);
                $this->assertEquals("Item {$w}.{$j}", $result['items'][$idx]['title']);
                $this->assertEquals("Author {$w}.{$j}", $result['items'][$idx]['author']);
                $this->assertEquals("<div class=\"entry\">{$w}.{$j}</div>", $result['items'][$idx]['html']);
                $this->assertEquals("http://example.com/item{$w}.{$j}", $result['items'][$idx]['url']);
                $this->assertEquals($j == 3 || $j == 4 ? 1 : 0, $result['items'][$idx]['is_saved']);
                $this->assertEquals($j == 2 || $j == 4 ? 1 : 0, $result['items'][$idx]['is_read']);
                $this->assertEquals(1000000000 + 10 * $w + $j, $result['items'][$idx]['created_on_time']);
                $this->assertArrayNotHasKey('added_on_time', $result['items'][$idx]);
            }
        }
    }

    public function testWriteItems() {
        $response = new Response(2, 'json');
        $response->setUser('86b175152449a29e2c217c90965659d8');

        $response->mark('item', 'read', 5);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        $this->assertEquals(1, $result['items'][4]['is_read']);

        $response->mark('item', 'unread', 5);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        $this->assertEquals(0, $result['items'][4]['is_read']);

        $response->mark('item', 'saved', 5);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        $this->assertEquals(1, $result['items'][4]['is_saved']);

        $response->mark('item', 'unsaved', 5);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        $this->assertEquals(0, $result['items'][4]['is_saved']);
    }
}
