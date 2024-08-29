<?php

namespace mttzzz\AmoClient\Tests;

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

    public function testLinkCustomer() {}
}
