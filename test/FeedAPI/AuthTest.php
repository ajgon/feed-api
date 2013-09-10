<?php

namespace FeedAPI;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    public function testApiKeyParser()
    {
        $api_key = '!@#$%^&*()_+-={}[]:"|;\'\<>?,./`~0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $auth = new Auth($api_key);

        $this->assertEquals('0123456789abcdef', $auth->getApiKey());
    }

    public function testInvalidApiKey()
    {
        $api_key = md5('loremipsum');

        $auth = new Auth($api_key);

        $this->assertEquals(false, $auth->validate());
    }

    public function testValidApiKey()
    {
        global $fixtures;
        $api_key = $fixtures['users'][0]['api_key'];

        $auth = new Auth($api_key);

        $this->assertEquals(true, $auth->validate());
    }
}
