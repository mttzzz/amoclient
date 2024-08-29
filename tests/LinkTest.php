<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Models\Link;

class LinkTest extends BaseAmoClient
{
    public function testLink()
    {

        $leadId = $this->amoClient->leads->entityData(['name' => 'test'])->createGetId();
        $contactId = $this->amoClient->contacts->entityData(['name' => 'test'])->createGetId();
        $companyId = $this->amoClient->companies->entityData(['name' => 'test'])->createGetId();
        $customerId = $this->amoClient->customers->entityData(['name' => 'test', 'next_date' => time()])->createGetId();

        $this->amoClient->leads->entity($leadId)->links->contact($contactId)->link();
        $this->amoClient->leads->entity($leadId)->links->companies($companyId)->link();

        $found = $this->amoClient->leads->withContacts()->find($leadId)->toArray();

        $this->assertEquals($found['_embedded']['contacts'][0]['id'], $contactId);
        $this->assertEquals($found['_embedded']['companies'][0]['id'], $companyId);

        $this->amoClient->contacts->entity($contactId)->links->customers($customerId)->link();
        $found2 = $this->amoClient->contacts->withCustomers()->find($contactId)->toArray();
        $this->assertEquals($found2['_embedded']['customers'][0]['id'], $customerId);

        $response = $this->amoClient->ajax->postForm('/ajax/contacts/multiple/delete/', ['ID' => [$contactId]]);
        $this->assertEquals('success', $response['status']);

        $response = $this->amoClient->ajax->postForm('/ajax/companies/multiple/delete/', ['ID' => [$companyId]]);
        $this->assertEquals('success', $response['status']);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);

        $response = $this->amoClient->ajax->postJson('/ajax/v1/customers/set/', ['request' => ['customers' => ['delete' => [$customerId]]]]);
        $this->assertEquals(0, count($response['response']['customers']['delete']['errors']));

    }

    public function testLinkCustomerException()
    {

        $this->expectExceptionMessage('Customer can be linked only to contact or company');
        $this->amoClient->leads->entity()->links->customers(123)->link();

    }

    public function testLinkArrayOfEntities()
    {

        $leadId = $this->amoClient->leads->entityData(['name' => 'test'])->createGetId();
        $contactId = $this->amoClient->contacts->entityData(['name' => 'test'])->createGetId();
        $companyId = $this->amoClient->companies->entityData(['name' => 'test'])->createGetId();

        $leadEntity = $this->amoClient->leads->entity($leadId);

        $linkEntity = $leadEntity->links->entity();
        $linkEntity->to_entity_id = $leadId;
        $linkEntity->to_entity_type = 'leads';

        $this->amoClient->contacts->entity($contactId)->links->link([$linkEntity]);
        $this->amoClient->companies->entity($companyId)->links->link([$linkEntity]);

        $found = $this->amoClient->leads->withContacts()->find($leadId)->toArray();

        $this->assertEquals($found['_embedded']['contacts'][0]['id'], $contactId);
        $this->assertEquals($found['_embedded']['companies'][0]['id'], $companyId);

        $this->amoClient->contacts->entity($contactId)->links->unlink([$linkEntity]);
        $this->amoClient->companies->entity($companyId)->links->unlink([$linkEntity]);

        $found2 = $this->amoClient->leads->withContacts()->find($leadId)->toArray();

        $this->assertEmpty($found2['_embedded']['contacts']);
        $this->assertEmpty($found2['_embedded']['companies']);

        $response = $this->amoClient->ajax->postForm('/ajax/contacts/multiple/delete/', ['ID' => [$contactId]]);
        $this->assertEquals('success', $response['status']);

        $response = $this->amoClient->ajax->postForm('/ajax/companies/multiple/delete/', ['ID' => [$companyId]]);
        $this->assertEquals('success', $response['status']);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);

    }

    public function testLinkArrayOfEntitiesExceptionLink()
    {
        $contactId = $this->amoClient->contacts->entityData(['name' => 'test'])->createGetId();
        $entity = (new Link($this->amoClient->http, 'leads'))->entity();

        $this->expectException(AmoCustomException::class);

        try {
            $this->amoClient->contacts->entity($contactId)->links->link([$entity]);
        } finally {

            $response = $this->amoClient->ajax->postForm('/ajax/contacts/multiple/delete/', ['ID' => [$contactId]]);
            $this->assertEquals('success', $response['status']);
        }

    }

    public function testLinkArrayOfEntitiesExceptionUnlink()
    {
        $contactId = $this->amoClient->contacts->entityData(['name' => 'test'])->createGetId();
        $entity = (new Link($this->amoClient->http, 'leads'))->entity();

        $this->expectException(AmoCustomException::class);

        try {
            $this->amoClient->contacts->entity($contactId)->links->unlink([$entity]);
        } finally {

            $response = $this->amoClient->ajax->postForm('/ajax/contacts/multiple/delete/', ['ID' => [$contactId]]);
            $this->assertEquals('success', $response['status']);
        }

    }
}
