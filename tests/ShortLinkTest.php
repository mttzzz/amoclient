<?php

namespace mttzzz\AmoClient\Tests;

class ShortLinkTest extends BaseAmoClient
{
    public function testShortLink()
    {

        $contactId = $this->amoClient->contacts->entityData(['name' => 'test'])->createGetId();
        $shortLink = $this->amoClient->shortLinks->entity()->url('https://ya.ru')->setContactId($contactId);
        $response = $shortLink->create();
        $this->assertArrayHasKey('url', $response['_embedded']['short_links'][0]);
        $url = $shortLink->createGetUrl();
        $this->assertIsString($url);

        $response = $this->amoClient->ajax->postForm('/ajax/contacts/multiple/delete/', ['ID' => [$contactId]]);
        $this->assertEquals('success', $response['status']);

    }
}
