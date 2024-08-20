<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Entities\Call;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class CallTest extends BaseAmoClient
{
    public function testCallCreate()
    {
        $phone = '375296117699';
        $link = 'https://ya.ru';
        $source = 'asterisk';
        $duration = 0;
        $uniq = rand();

        $call = $this->amoClient->calls->entity();
        $this->assertNotNull($call);
        $this->assertInstanceOf(Call::class, $call);
        $createdCalls = $call
            ->uniq($uniq)
            ->duration($duration)
            ->phone($phone)
            ->link($link)
            ->source($source)
            ->directionInbound()
            ->statusSuccess()
            ->create();

        $this->assertIsArray($createdCalls);
        $this->assertArrayHasKey('_embedded', $createdCalls);
        $this->assertArrayHasKey('calls', $createdCalls['_embedded']);
        $this->assertIsArray($createdCalls['_embedded']['calls']);
        $this->assertEquals(1, count($createdCalls['_embedded']['calls']));
        $this->assertArrayHasKey('id', $createdCalls['_embedded']['calls'][0]);

    }

    public function testCallCreateException()
    {
        $call = $this->amoClient->calls->entity();
        $this->expectException(AmoCustomException::class);
        $call->create();

    }
}
