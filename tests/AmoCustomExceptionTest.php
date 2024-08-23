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
    public function testAmoCustomExceptionWithConnectionException()
    {
        $connectionException = new ConnectionException('Connection error', 500);

        $amoCustomException = new AmoCustomException($connectionException);

        $this->assertEquals('Unknown error (ConnectionException)', $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }

    public function testAmoCustomExceptionWithRequestException()
    {
        $response = new Response(new GuzzleResponse(500, [], json_encode(['error' => 'Internal Server Error'])));
        $requestException = new RequestException($response);

        $amoCustomException = new AmoCustomException($requestException);

        $expectedMessage = json_encode(['error' => 'Internal Server Error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $this->assertEquals($expectedMessage, $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }

    public function testAmoCustomExceptionWithPaymentRequired()
    {
        $response = new Response(new GuzzleResponse(402, [], ''));
        $requestException = new RequestException($response);

        $amoCustomException = new AmoCustomException($requestException);

        $this->assertEquals('Амо не оплачен', $amoCustomException->getMessage());
        $this->assertEquals(402, $amoCustomException->getCode());
    }

    public function testInvalidJsonResponse()
    {
        // Создаем GuzzleResponse с некорректным JSON
        $guzzleResponse = new GuzzleResponse(200, [], 'Invalid JSON');

        // Создаем Response на основе GuzzleResponse
        $response = new Response($guzzleResponse);

        // Создаем RequestException с этим Response
        $requestException = new RequestException($response);

        // Создаем AmoCustomException с RequestException
        $exception = new AmoCustomException($requestException);

        // Проверяем, что сообщение исключения соответствует ожидаемому
        $this->assertEquals('Invalid JSON response (RequestException)', $exception->getMessage());
    }

    public function testAmoCustomExceptionWithInvalidJson()
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
        $this->assertEquals('Invalid JSON response (RequestException)', $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }

    public function testAmoCustomExceptionWithValidJson()
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

    public function testAmoCustomExceptionWithUnserializableJson()
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
        $this->assertEquals('Invalid JSON response (RequestException)', $amoCustomException->getMessage());
        $this->assertEquals(500, $amoCustomException->getCode());
    }
}
