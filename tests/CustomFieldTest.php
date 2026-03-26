<?php

namespace mttzzz\AmoClient\Tests;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Models\CustomField;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\MockObject\MockObject;

class CustomFieldTest extends BaseAmoClient
{
    protected CustomField $customField;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_custom_field_create()
    {
        $customFields = $this->amoClient->leads->customFields()->get();

        $this->assertIsArray($customFields);
        $this->assertNotEmpty($customFields);
        $this->assertArrayHasKey('id', $customFields[0]);

        return $customFields[0]['id'];
    }

    #[Depends('test_custom_field_create')]
    public function test_custom_field_update(int $customFieldId)
    {
        $customField = $this->amoClient->leads->customFields()->entity($customFieldId);
        $customField->name = 'Updated Custom Field';

        try {
            $response = $customField->update();
        } catch (AmoCustomException $e) {
            $this->skipIfUnsupportedAmoResponse($e, ['NotSupportedChoice'], 'Lead custom field update is not supported for the selected field in this account.');
            throw $e;
        }

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('custom_fields', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['custom_fields']);
        $this->assertEquals(1, count($response['_embedded']['custom_fields']));
        $this->assertArrayHasKey('id', $response['_embedded']['custom_fields'][0]);

        $updated = $response['_embedded']['custom_fields'][0];
        $this->assertEquals('Updated Custom Field', $updated['name']);
    }

    #[Depends('test_custom_field_create')]
    public function test_custom_field_find(int $customFieldId)
    {
        $customField = $this->amoClient->leads->customFields()->find($customFieldId);
        $this->assertIsArray($customField);
        $this->assertArrayHasKey('id', $customField);
        $this->assertEquals($customFieldId, $customField['id']);
    }

    public function test_custom_field_find_exception()
    {
        $customFieldId = 123; // Некорректный ID для теста

        // Создаем мок для PendingRequest
        /** @var PendingRequest|MockObject $httpMock */
        $httpMock = $this->createMock(PendingRequest::class);

        // Создаем реальный объект Response с необходимыми данными
        $response = new Response(
            new \GuzzleHttp\Psr7\Response(500, [], 'Internal Server Error')
        );

        // Настраиваем мок так, чтобы метод get выбрасывал RequestException с реальным объектом Response
        $httpMock->method('get')->will($this->throwException(new RequestException($response)));

        // Создаем экземпляр класса, который мы тестируем, и подставляем мок
        $customFieldService = new CustomField($httpMock, 'leads');

        // Ожидаем, что будет выброшено AmoCustomException
        $this->expectException(AmoCustomException::class);

        $customFieldService->find($customFieldId);
    }
}
