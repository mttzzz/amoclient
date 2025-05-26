<?php

namespace mttzzz\AmoClient\Tests;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use PHPUnit\Framework\TestCase;

class AmoCustomExceptionTest extends TestCase
{
    public function test_amo_custom_exception_with_connection_exception()
    {
        $connectionException = new ConnectionException('Connection error', 500);

        $amoCustomException = new AmoCustomException($connectionException);

        $this->assertEquals('Unknown error (ConnectionException)', $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }

    public function test_amo_custom_exception_with_request_exception()
    {
        $response = new Response(new GuzzleResponse(500, [], json_encode(['error' => 'Internal Server Error'])));
        $requestException = new RequestException($response);

        $amoCustomException = new AmoCustomException($requestException);

        $expectedMessage = json_encode(['error' => 'Internal Server Error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $this->assertEquals($expectedMessage, $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }

    public function test_amo_custom_exception_with_payment_required()
    {
        $response = new Response(new GuzzleResponse(402, [], ''));
        $requestException = new RequestException($response);

        $amoCustomException = new AmoCustomException($requestException);

        $this->assertEquals('Амо не оплачен', $amoCustomException->getMessage());
        $this->assertEquals(402, $amoCustomException->getCode());
    }

    public function test_invalid_json_response()
    {
        // Создаем GuzzleResponse с некорректным JSON
        $guzzleResponse = new GuzzleResponse(500, [], 'Invalid JSON');

        // Создаем Response на основе GuzzleResponse
        $response = new Response($guzzleResponse);

        // Создаем RequestException с этим Response
        $requestException = new RequestException($response);

        // Создаем AmoCustomException с RequestException
        $amoCustomException = new AmoCustomException($requestException);

        // Проверяем, что сообщение исключения соответствует ожидаемому
        $this->assertEquals("HTTP request returned status code 500:\nInvalid JSON\n", $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }

    public function test_amo_custom_exception_with_invalid_json()
    {
        // Создаем GuzzleResponse с некорректным JSON
        $guzzleResponse = new GuzzleResponse(500, [], 'Invalid JSON');

        // Создаем Response на основе GuzzleResponse
        $response = new Response($guzzleResponse);

        // Создаем RequestException с этим Response
        $requestException = new RequestException($response);

        // Создаем AmoCustomException с RequestException
        $amoCustomException = new AmoCustomException($requestException);

        // Проверяем, что сообщение исключения соответствует ожидаемому
        $this->assertEquals("HTTP request returned status code 500:\nInvalid JSON\n", $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }

    public function test_amo_custom_exception_with_valid_json()
    {
        // Создаем GuzzleResponse с корректным JSON
        $guzzleResponse = new GuzzleResponse(500, [], json_encode(['error' => 'Some error']));

        // Создаем Response на основе GuzzleResponse
        $response = new Response($guzzleResponse);

        // Создаем RequestException с этим Response
        $requestException = new RequestException($response);

        // Создаем AmoCustomException с RequestException
        $amoCustomException = new AmoCustomException($requestException);

        // Проверяем, что сообщение исключения соответствует ожидаемому
        $expectedMessage = json_encode(['error' => 'Some error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $this->assertEquals($expectedMessage, $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }

    public function test_amo_custom_exception_with_unserializable_json()
    {
        // Создаем объект с циклической ссылкой
        $a = new \stdClass;
        $b = new \stdClass;
        $a->b = $b;
        $b->a = $a;

        // Создаем GuzzleResponse с этим объектом
        $guzzleResponse = new GuzzleResponse(500, [], json_encode($a));

        // Создаем Response на основе GuzzleResponse
        $response = new Response($guzzleResponse);

        // Создаем RequestException с этим Response
        $requestException = new RequestException($response);

        // Создаем AmoCustomException с RequestException
        $amoCustomException = new AmoCustomException($requestException);

        // Проверяем, что сообщение исключения соответствует ожидаемому
        $this->assertEquals('HTTP request returned status code 500', $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }
}
