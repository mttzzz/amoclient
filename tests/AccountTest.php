<?php

namespace mttzzz\AmoClient\Tests;

class AccountTest extends BaseAmoClient
{
    public function testAccountGet()
    {
        $account = $this->amoClient->account->get();

        // Проверка, что ответ не пустой
        $this->assertNotEmpty($account);
        $this->assertIsArray($account);
    }

    public function testAccountContainsKeys()
    {
        $account = $this->amoClient->account->get();

        // Проверка, что массив содержит ключ 'id'
        $this->assertArrayHasKey('id', $account);

        // Проверка, что массив содержит ключ 'name'
        $this->assertArrayHasKey('name', $account);

        // Проверка, что массив содержит ключ 'subdomain'
        $this->assertArrayHasKey('subdomain', $account);
    }

    public function testAccountWithAmojoId()
    {
        $account = $this->amoClient->account->withAmojoId()->get();
        $this->assertArrayHasKey('amojo_id', $account);
    }

    public function testAccountWithAmojoRights()
    {
        $account = $this->amoClient->account->withAmojoRights()->get();

        $this->assertArrayHasKey('_embedded', $account);
        $this->assertArrayHasKey('amojo_rights', $account['_embedded']);
    }

    public function testAccountWithUsersGroups()
    {
        $account = $this->amoClient->account->withUsersGroups()->get();

        $this->assertArrayHasKey('_embedded', $account);
        $this->assertArrayHasKey('users_groups', $account['_embedded']);
    }

    public function testAccountWithTaskTypes()
    {
        $account = $this->amoClient->account->withTaskTypes()->get();
        $this->assertArrayHasKey('_embedded', $account);
        $this->assertArrayHasKey('task_types', $account['_embedded']);
    }

    public function testAccountWithVersion()
    {
        $account = $this->amoClient->account->withVersion()->get();
        $this->assertArrayHasKey('version', $account);
    }

    public function testAccountWithEntityNames()
    {
        $account = $this->amoClient->account->withEntityNames()->get();
        $this->assertArrayHasKey('entity_names', $account);
    }

    public function testAccountWithDatetimeSettings()
    {
        $account = $this->amoClient->account->withDatetimeSettings()->get();
        $this->assertArrayHasKey('_embedded', $account);
        $this->assertArrayHasKey('datetime_settings', $account['_embedded']);
    }
}