<?php

namespace mttzzz\AmoClient\Tests;

class UserTest extends BaseAmoClient
{
    public function test_users_get()
    {
        $users = $this->amoClient->users->get();
        $this->assertNotNull($users);
        $this->assertIsArray($users);
        $this->assertGreaterThanOrEqual(1, count($users));

    }

    public function test_user_contains_keys()
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

    public function test_with_role()
    {
        $users = $this->amoClient->users->withRole()->get();
        $this->assertArrayHasKey('_embedded', $users[0]);
        $this->assertArrayHasKey('roles', $users[0]['_embedded']);

    }

    public function test_with_group()
    {
        $users = $this->amoClient->users->withGroup()->get();
        $this->assertArrayHasKey('_embedded', $users[0]);
        $this->assertArrayHasKey('groups', $users[0]['_embedded']);
    }

    public function test_with_uuid()
    {
        $users = $this->amoClient->users->withUuid()->get();
        $this->assertArrayHasKey('uuid', $users[0]);
    }

    public function test_with_amojo_id()
    {
        $users = $this->amoClient->users->withAmojoId()->get();
        $this->assertArrayHasKey('amojo_id', $users[0]);
    }

    public function test_find()
    {
        $users = $this->amoClient->users->withGroup()->get();
        $user = $this->amoClient->users->find($users[0]['id']);
        $this->assertNotNull($user);
    }
}
