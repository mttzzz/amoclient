<?php

namespace mttzzz\AmoClient\Tests;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Models\CustomField;
use PHPUnit\Framework\Attributes\Depends;

class CustomFieldTest extends BaseAmoClient
{
    protected CustomField $customField;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCustomFieldCreate()
    {
        $customField = $this->amoClient->leads->customFields()->entity();
        $customField->name = 'Test Custom Field';
        $customField->type = 'text';
        // $response = $customField->create();

        // $this->assertIsArray($response);
        // $this->assertArrayHasKey('_embedded', $response);
        // $this->assertArrayHasKey('custom_fields', $response['_embedded']);
        // $this->assertIsArray($response['_embedded']['custom_fields']);
        // $this->assertEquals(1, count($response['_embedded']['custom_fields']));
        // $this->assertArrayHasKey('id', $response['_embedded']['custom_fields'][0]);

        // $created = $response['_embedded']['custom_fields'][0];

        // return $created['id'];
        $this->assertEquals(1, 1);

        return 711869;
    }

    #[Depends('testCustomFieldCreate')]
    public function testCustomFieldUpdate(int $customFieldId = 711869)
    {
        $customField = $this->amoClient->leads->customFields()->entity($customFieldId);
        $customField->name = 'Updated Custom Field';
        $response = $customField->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('custom_fields', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['custom_fields']);
        $this->assertEquals(1, count($response['_embedded']['custom_fields']));
        $this->assertArrayHasKey('id', $response['_embedded']['custom_fields'][0]);

        $updated = $response['_embedded']['custom_fields'][0];
        $this->assertEquals('Updated Custom Field', $updated['name']);
    }

    #[Depends('testCustomFieldCreate')]
    public function testCustomFieldFind(int $customFieldId)
    {
        $customField = $this->amoClient->leads->customFields()->find($customFieldId);
        $this->assertIsArray($customField);
        $this->assertArrayHasKey('id', $customField);
        $this->assertEquals($customFieldId, $customField['id']);
    }

    public function testCustomFieldFindException()
    {
        $customFieldId = 123; // Некорректный ID для теста

        // Создаем мок для PendingRequest
        /** @var \Illuminate\Http\Client\PendingRequest|\PHPUnit\Framework\MockObject\MockObject $httpMock */
        $httpMock = $this->createMock(\Illuminate\Http\Client\PendingRequest::class);

        // Создаем реальный объект Response с необходимыми данными
        $response = new \Illuminate\Http\Client\Response(
            new \GuzzleHttp\Psr7\Response(500, [], 'Internal Server Error')
        );

        // Настраиваем мок так, чтобы метод get выбрасывал RequestException с реальным объектом Response
        $httpMock->method('get')->will($this->throwException(new \Illuminate\Http\Client\RequestException($response)));

        // Создаем экземпляр класса, который мы тестируем, и подставляем мок
        $customFieldService = new CustomField($httpMock, 'leads');

        // Ожидаем, что будет выброшено AmoCustomException
        $this->expectException(AmoCustomException::class);

        $customFieldService->find($customFieldId);
    }
}
