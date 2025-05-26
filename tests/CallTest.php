<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Entities\Call;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class CallTest extends BaseAmoClient
{
    public function test_call_create()
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
            ->result('no answer')
            ->responsibleUserId(1693819)
            ->createdBy(1693819)
            ->updatedBy(1693819)
            ->createdAt(time() - 1000)
            ->updatedAt(time() - 500)
            ->requestId('123')
            ->phone($phone)
            ->link($link)
            ->source($source)
            ->directionOutbound()
            ->directionInbound()
            ->statusLeaveMessage()
            ->statusCallLater()
            ->statusAbsent()
            ->statusWrongNumber()
            ->statusError()
            ->statusBusy()
            ->statusSuccess()
            ->create();

        $this->assertArrayHasKey('id', $createdCalls['_embedded']['calls'][0]);

    }

    public function test_call_filter_by_result()
    {
        $call = $this->amoClient->calls->entity();
        $this->expectException(AmoCustomException::class);
        $call->create();

    }

    public function test_call_create_exception()
    {
        $call = $this->amoClient->calls->entity();
        $this->expectException(AmoCustomException::class);
        $call->create();

    }
}
