<?php

namespace FeedAPI;

class FeedsTest extends \PHPUnit_Framework_TestCase
{
    public function testFeedsJSON() {
        $response = new Response(2, 'json');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeFeeds();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['feeds']);
        $this->assertCount(5, $result['feeds']);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($i + 1, $result['feeds'][$i]['favicon_id']);
            $this->assertArrayNotHasKey('feed_type', $result['feeds'][$i]);
            $this->assertEquals('Test Feed #' . ($i + 1), $result['feeds'][$i]['title']);
            $this->assertEquals('http://example.com/feed' . ($i + 1), $result['feeds'][$i]['url']);
            $this->assertEquals('http://feed' . ($i + 1) . '.example.com/', $result['feeds'][$i]['site_url']);
            $this->assertEquals(0, $result['feeds'][$i]['is_spark']);
            $this->assertEquals(1000000001 + $i, $result['feeds'][$i]['last_updated_on_time']);
        }
    }

    public function testFeedsXML() {
        $response = new Response(2, 'xml');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeFeeds();

        $result = new \DOMDocument();
        $result->loadXML($response->render(true));

        $this->assertEquals(1, $result->getElementsByTagName('feeds')->length);
        $this->assertEquals(5, $result->getElementsByTagName('feed')->length);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($i + 1, $result->getElementsByTagName('feed')->item($i)->childNodes->item(1)->textContent);
            $this->assertEquals('Test Feed #' . ($i + 1), $result->getElementsByTagName('feed')->item($i)->childNodes->item(2)->textContent);
            $this->assertEquals('http://example.com/feed' . ($i + 1), $result->getElementsByTagName('feed')->item($i)->childNodes->item(3)->textContent);
            $this->assertEquals('http://feed' . ($i + 1) . '.example.com/', $result->getElementsByTagName('feed')->item($i)->childNodes->item(4)->textContent);
            $this->assertEquals(0, $result->getElementsByTagName('feed')->item($i)->childNodes->item(5)->textContent);
            $this->assertEquals(1000000001 + $i, $result->getElementsByTagName('feed')->item($i)->childNodes->item(6)->textContent);
        }
    }

    public function testACLFeedsJSON() {
        $response = new Response(2, 'json');
        $response->setUser('105404aef1fb9f9952e8433294fe44a8');
        $response->includeFeeds();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['feeds']);
        $this->assertCount(3, $result['feeds']);
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($i * 2 + 1, $result['feeds'][$i]['favicon_id']);
            $this->assertArrayNotHasKey('feed_type', $result['feeds'][$i]);
            $this->assertEquals('Test Feed #' . ($i * 2 + 1), $result['feeds'][$i]['title']);
            $this->assertEquals('http://example.com/feed' . ($i * 2 + 1), $result['feeds'][$i]['url']);
            $this->assertEquals('http://feed' . ($i * 2 + 1) . '.example.com/', $result['feeds'][$i]['site_url']);
            $this->assertEquals(0, $result['feeds'][$i]['is_spark']);
            $this->assertEquals(1000000001 + $i * 2, $result['feeds'][$i]['last_updated_on_time']);
        }
    }

    public function testWriteFeeds() {
        $response = new Response(2, 'json');
        $response->setUser('86b175152449a29e2c217c90965659d8');

        $response->mark('feed', 'read', 2, 1000000024);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        for ($i = 5; $i < 8; $i++) {
            $this->assertEquals(1, $result['items'][$i]['is_read']);
        }

        $response->mark('feed', 'unread', 2, 1000000024);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        for ($i = 5; $i < 8; $i++) {
            $this->assertEquals(0, $result['items'][$i]['is_read']);
        }

        $response->mark('feed', 'saved', 2, 1000000024);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        for ($i = 5; $i < 8; $i++) {
            $this->assertEquals(1, $result['items'][$i]['is_saved']);
        }

        $response->mark('feed', 'unsaved', 2, 1000000024);
        $response->includeItems(true);
        $result = json_decode($response->render(true), true);
        for ($i = 5; $i < 8; $i++) {
            $this->assertEquals(0, $result['items'][$i]['is_saved']);
        }

        $response->mark('item', 'read', 7);
        $response->mark('item', 'saved', 8);
    }
}
