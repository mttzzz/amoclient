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

        $shortLink2 = $this->amoClient->shortLinks->entity()->url('https://ya.ru')->setContactId($contactId);
        $shortLink3 = $this->amoClient->shortLinks->entity()->url('https://ya.ru')->setContactId($contactId);

        $response2 = $this->amoClient->shortLinks->create([$shortLink2, $shortLink3]);
        $this->assertEquals(2, count($response2['_embedded']['short_links']));

        $response3 = $this->amoClient->shortLinks->create([]);
        $this->assertEmpty($response3);

        $response = $this->amoClient->ajax->postForm('/ajax/contacts/multiple/delete/', ['ID' => [$contactId]]);
        $this->assertEquals('success', $response['status']);

    }
}
