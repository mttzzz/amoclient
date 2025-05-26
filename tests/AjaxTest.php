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

    public function test_get()
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

    public function test_post_json()
    {
        $account = new OctaneAccount;
        $account->domain = 'domain';
        $account->subdomain = 'subdomain';
        $ajax = new Ajax($account, $this->amoClient->http);
        $response = $ajax->postJson('https://jsonplaceholder.typicode.com/posts', ['title' => 'foo', 'body' => 'bar', 'userId' => 1]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function test_post_form()
    {
        $account = new OctaneAccount;
        $account->domain = 'domain';
        $account->subdomain = 'subdomain';
        $ajax = new Ajax($account, $this->amoClient->http);
        $response = $ajax->postForm('https://jsonplaceholder.typicode.com/posts', ['title' => 'foo', 'body' => 'bar', 'userId' => 1]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function test_patch()
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

    public function test_delete()
    {
        $account = new OctaneAccount;
        $account->domain = 'domain';
        $account->subdomain = 'subdomain';
        $ajax = new Ajax($account, $this->amoClient->http);
        $response = $ajax->delete('https://jsonplaceholder.typicode.com/posts/1');

        $this->assertIsArray($response);
    }
}
