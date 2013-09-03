<?php

namespace RSSAPI;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    protected static $api_version;

    public function setUp()
    {
        self::$api_version = rand(0, 42);
    }

    public function testRawResponseJSON()
    {
        $response = new Response(self::$api_version, 'json');

        $this->assertEquals(
            json_encode(array('api_version' => self::$api_version)),
            $response->render(true)
        );
    }

    public function testRawResponseXML()
    {
        $response = new Response(self::$api_version, 'xml');

        $this->assertEquals(
            '<?xml version="1.0" encoding="utf-8"?><response><api_version>' . self::$api_version . '</api_version></response>',
            $response->render(true)
        );
    }

    public function testAuthResponseJSON()
    {
        $response = new Response(self::$api_version, 'json');
        $response->setAuth(true);
        $result = json_decode($response->render(true));

        $this->assertEquals('1', $result->auth);

        $response->setAuth(false);
        $result = json_decode($response->render(true));

        $this->assertEquals('0', $result->auth);
    }

    public function testAuthResponseXML()
    {
        $response = new Response(self::$api_version, 'xml');
        $response->setAuth(true);

        $this->assertRegExp('/<auth>1<\/auth>/', $result = $response->render(true));

        $response->setAuth(false);

        $this->assertRegExp('/<auth>0<\/auth>/', $result = $response->render(true));
    }

    public function testLastRefreshedOnTime()
    {
        $response = new Response(self::$api_version, 'json');
        $response->setAuth(true);
        $response->includeLastRefreshsedOnTime('105404aef1fb9f9952e8433294fe44a8');
        $result = json_decode($response->render(true), true);

        $this->assertEquals(1378236337, $result['last_refreshed_on_time']);
    }
}
