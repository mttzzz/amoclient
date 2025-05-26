<?php

namespace mttzzz\AmoClient\Tests;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Models\Account;

class AccountTest extends BaseAmoClient
{
    public function test_account_get()
    {
        $account = $this->amoClient->account->get();

        // Проверка, что ответ не пустой
        $this->assertNotEmpty($account);
        $this->assertIsArray($account);
    }

    public function test_account_get_exception()
    {
        // Создаем мок для PendingRequest
        /** @var \Illuminate\Http\Client\PendingRequest|\PHPUnit\Framework\MockObject\MockObject $httpMock */
        $httpMock = $this->createMock(PendingRequest::class);

        // Создаем реальный объект Response с необходимыми данными
        $response = new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(500, [], 'Internal Server Error'));

        // Настраиваем мок так, чтобы метод get выбрасывал RequestException с реальным объектом Response
        $httpMock->method('get')->will($this->throwException(new RequestException($response)));

        // Создаем экземпляр класса, который мы тестируем, и подставляем мок
        $account = new Account($httpMock, $this->amoClient->accountId);

        // Ожидаем, что будет выброшено AmoCustomException
        $this->expectException(AmoCustomException::class);

        // Вызываем метод get, который должен выбросить исключение
        $account->get();
    }

    public function test_account_contains_keys()
    {
        $account = $this->amoClient->account->get();

        // Проверка, что массив содержит ключ 'id'
        $this->assertArrayHasKey('id', $account);

        // Проверка, что массив содержит ключ 'name'
        $this->assertArrayHasKey('name', $account);

        // Проверка, что массив содержит ключ 'subdomain'
        $this->assertArrayHasKey('subdomain', $account);
    }

    public function test_account_with_amojo_id()
    {
        $account = $this->amoClient->account->withAmojoId()->get();
        $this->assertArrayHasKey('amojo_id', $account);
    }

    public function test_account_with_amojo_rights()
    {
        $account = $this->amoClient->account->withAmojoRights()->get();

        $this->assertArrayHasKey('_embedded', $account);
        $this->assertArrayHasKey('amojo_rights', $account['_embedded']);
    }

    public function test_account_with_users_groups()
    {
        $account = $this->amoClient->account->withUsersGroups()->get();

        $this->assertArrayHasKey('_embedded', $account);
        $this->assertArrayHasKey('users_groups', $account['_embedded']);
    }

    public function test_account_with_task_types()
    {
        $account = $this->amoClient->account->withTaskTypes()->get();
        $this->assertArrayHasKey('_embedded', $account);
        $this->assertArrayHasKey('task_types', $account['_embedded']);
    }

    public function test_account_with_version()
    {
        $account = $this->amoClient->account->withVersion()->get();
        $this->assertArrayHasKey('version', $account);
    }

    public function test_account_with_entity_names()
    {
        $account = $this->amoClient->account->withEntityNames()->get();
        $this->assertArrayHasKey('entity_names', $account);
    }

    public function test_account_with_datetime_settings()
    {
        $account = $this->amoClient->account->withDatetimeSettings()->get();
        $this->assertArrayHasKey('_embedded', $account);
        $this->assertArrayHasKey('datetime_settings', $account['_embedded']);
    }

    public function test_account_request_exception()
    {
        $this->expectException(RequestException::class);

        // Создаем объект GuzzleResponse
        $guzzleResponse = new GuzzleResponse(500, [], 'Test exception');
        // Оборачиваем его в объект Illuminate\Http\Client\Response
        $response = new Response($guzzleResponse);

        // Мокирование метода get, чтобы он выбрасывал исключение
        $accountMock = $this->createMock(Account::class);
        $accountMock->method('get')->will($this->throwException(new RequestException($response)));

        $this->amoClient->account = $accountMock;

        // Выполнение реального вызова
        $this->amoClient->account->get();
    }
}
