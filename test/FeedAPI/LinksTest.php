<?php

namespace FeedAPI;

class LinksTest extends \PHPUnit_Framework_TestCase
{
    public function testLinksJSON() {
        $response = new Response(2, 'json');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeLinks();

        $result = json_decode($response->render(true), true);

        $this->assertEmpty($result['links']);
    }

    public function testLinksXML() {
        $response = new Response(2, 'xml');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeLinks();

        $result = new \DOMDocument();
        $result->loadXML($response->render(true));

        $this->assertEquals(1, $result->getElementsByTagName('links')->length);
        $this->assertEquals(0, $result->getElementsByTagName('link')->length);
    }
}
