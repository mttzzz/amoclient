<?php

namespace mttzzz\AmoClient\Tests;

class UserTest extends BaseAmoClient
{
    public function testUsersGet()
    {
        $users = $this->amoClient->users->get();
        $this->assertNotNull($users);
        $this->assertIsArray($users);
        $this->assertGreaterThanOrEqual(1, count($users));

    }

    public function testUserContainsKeys()
    {
        $users = $this->amoClient->users->get();

        // Проверка, что массив содержит ключ 'id'
        $this->assertArrayHasKey('id', $users[0]);

        // Проверка, что массив содержит ключ 'name'
        $this->assertArrayHasKey('name', $users[0]);

        // Проверка, что массив содержит ключ 'email'
        $this->assertArrayHasKey('email', $users[0]);

        // Проверка, что массив содержит ключ 'lang'
        $this->assertArrayHasKey('lang', $users[0]);
    }

    public function testWithRole()
    {
        $users = $this->amoClient->users->withRole()->get();
        $this->assertArrayHasKey('_embedded', $users[0]);
        $this->assertArrayHasKey('roles', $users[0]['_embedded']);

    }

    public function testWithGroup()
    {
        $users = $this->amoClient->users->withGroup()->get();
        $this->assertArrayHasKey('_embedded', $users[0]);
        $this->assertArrayHasKey('groups', $users[0]['_embedded']);
    }

    public function testWithUuid()
    {
        $users = $this->amoClient->users->withUuid()->get();
        $this->assertArrayHasKey('uuid', $users[0]);
    }

    public function testWithAmojoId()
    {
        $users = $this->amoClient->users->withAmojoId()->get();
        $this->assertArrayHasKey('amojo_id', $users[0]);
    }

    public function testFind()
    {
        $users = $this->amoClient->users->withGroup()->get();
        $user = $this->amoClient->users->find($users[0]['id']);
        $this->assertNotNull($user);
    }
}
