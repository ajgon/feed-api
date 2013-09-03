<?php

namespace RSSAPI;

class FeedsGroupsTest extends \PHPUnit_Framework_TestCase
{
    public function testGroupsJSON() {
        $response = new Response(2, 'json');
        $response->includeFeedsGroups();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['feeds_groups']);
        $this->assertCount(5, $result['feeds_groups']);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($i + 1, $result['feeds_groups'][$i]['group_id']);
            $this->assertEquals($this->concatenateIds($i), $result['feeds_groups'][$i]['feed_ids']);
        }
    }

    public function testGroupsXML() {
        $response = new Response(2, 'xml');
        $response->includeFeedsGroups();

        $result = new \DOMDocument();
        $result->loadXML($response->render(true));

        $this->assertEquals(1, $result->getElementsByTagName('feeds_groups')->length);
        $this->assertEquals(5, $result->getElementsByTagName('feeds_group')->length);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($i + 1, $result->getElementsByTagName('feeds_group')->item($i)->childNodes->item(0)->textContent);
            $this->assertEquals($this->concatenateIds($i), $result->getElementsByTagName('feeds_group')->item($i)->childNodes->item(1)->textContent);
        }
    }

    private function concatenateIds($i) {
        $ids = array($i + 1, (($i + 3) % 5 == 0 ) ? 5 : ($i + 3) % 5);
        sort($ids, SORT_NUMERIC);
        return implode(',', $ids);
    }
}
