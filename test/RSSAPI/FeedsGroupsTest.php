<?php

namespace RSSAPI;

class FeedsGroupsTest extends \PHPUnit_Framework_TestCase
{
    public function testFeedsGroupsJSON() {
        $response = new Response(2, 'json');
        $response->setUser('86b175152449a29e2c217c90965659d8');
        $response->includeFeedsGroups();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['feeds_groups']);
        $this->assertCount(5, $result['feeds_groups']);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($i + 1, $result['feeds_groups'][$i]['group_id']);
            $this->assertEquals($this->concatenateIds($i), $result['feeds_groups'][$i]['feed_ids']);
        }
    }

    public function testFeedsGroupsXML() {
        $response = new Response(2, 'xml');
        $response->setUser('86b175152449a29e2c217c90965659d8');
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

    public function testACLFeedsGroupsJSON() {
        $response = new Response(2, 'json');
        $response->setUser('105404aef1fb9f9952e8433294fe44a8');
        $response->includeFeedsGroups();

        $result = json_decode($response->render(true), true);

        $this->assertNotEmpty($result['feeds_groups']);
        $this->assertCount(4, $result['feeds_groups']);
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals($i > 0 ? $i + 2 : $i + 1, $result['feeds_groups'][$i]['group_id']);
            $this->assertEquals($this->concatenateIds($i > 0 ? $i + 1 : $i, array(1, 3, 5)), $result['feeds_groups'][$i]['feed_ids']);
        }
    }

    private function concatenateIds($i, $only = array(1, 2, 3, 4, 5)) {
        $ids = array_intersect($only, array($i + 1, (($i + 3) % 5 == 0 ) ? 5 : ($i + 3) % 5));

        sort($ids, SORT_NUMERIC);
        return implode(',', $ids);
    }
}
