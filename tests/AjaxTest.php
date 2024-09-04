<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Ajax;
use mttzzz\AmoClient\Helpers\OctaneAccount;

class AjaxTest extends BaseAmoClient
{
    protected function setUp(): void
    {
        parent::setUp();

    }

    public function testGet()
    {
        $account = new OctaneAccount;
        $account->domain = 'domain';
        $account->subdomain = 'subdomain';
        $ajax = new Ajax($account, $this->amoClient->http);
        $response = $ajax->get('https://jsonplaceholder.typicode.com/posts/1');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals(1, $response['id']);
    }

    public function testPostJson()
    {
        $account = new OctaneAccount;
        $account->domain = 'domain';
        $account->subdomain = 'subdomain';
        $ajax = new Ajax($account, $this->amoClient->http);
        $response = $ajax->postJson('https://jsonplaceholder.typicode.com/posts', ['title' => 'foo', 'body' => 'bar', 'userId' => 1]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testPostForm()
    {
        $account = new OctaneAccount;
        $account->domain = 'domain';
        $account->subdomain = 'subdomain';
        $ajax = new Ajax($account, $this->amoClient->http);
        $response = $ajax->postForm('https://jsonplaceholder.typicode.com/posts', ['title' => 'foo', 'body' => 'bar', 'userId' => 1]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testPatch()
    {
        $account = new OctaneAccount;
        $account->domain = 'domain';
        $account->subdomain = 'subdomain';
        $ajax = new Ajax($account, $this->amoClient->http);
        $response = $ajax->patch('https://jsonplaceholder.typicode.com/posts/1', ['title' => 'foo']);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals(1, $response['id']);
    }

    public function testDelete()
    {
        $account = new OctaneAccount;
        $account->domain = 'domain';
        $account->subdomain = 'subdomain';
        $ajax = new Ajax($account, $this->amoClient->http);
        $response = $ajax->delete('https://jsonplaceholder.typicode.com/posts/1');

        $this->assertIsArray($response);
    }
}
