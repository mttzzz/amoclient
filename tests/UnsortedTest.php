<?php

namespace mttzzz\AmoClient\Tests;

class UnsortedTest extends BaseAmoClient
{
    public function testSip()
    {
        $sipEntity = $this->amoClient->unsorted->sip();
        $sipEntity->source_name = 'sipEntity';
        $sipEntity->source_uid = 'sipEntity';
        $sipEntity->addMetadata(rand(), rand(0, 100), 'asterisk', 'https://ya.ru', '2222222222', 0, '444444444', false);
        $created = $sipEntity->create();
        $this->assertArrayHasKey('uid', $created['_embedded']['unsorted'][0]);
        $declined = $this->amoClient->unsorted->decline($created['_embedded']['unsorted'][0]['uid'], 0);
        $this->assertEquals($created['_embedded']['unsorted'][0]['uid'], $declined['uid']);

        $sipEntity2 = $this->amoClient->unsorted->sip();
        $sipEntity2->source_name = 'sipEntity2';
        $sipEntity2->source_uid = 'sipEntity2';
        $sipEntity2->addMetadata(rand(), rand(0, 100), 'ssssss', 'https://ya.com', '11111111111', 0, '6666666', false);

        $created2 = $sipEntity2->create();
        $accepted = $this->amoClient->unsorted->accept($created2['_embedded']['unsorted'][0]['uid']);
        $this->assertArrayHasKey('id', $accepted['_embedded']['leads'][0]);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$accepted['_embedded']['leads'][0]['id']]]);
        $this->assertEquals('success', $response['status']);
    }
}
