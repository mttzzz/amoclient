<?php

namespace mttzzz\AmoClient\Tests;

use Carbon\Carbon;
use mttzzz\AmoClient\Entities\Contact;
use PHPUnit\Framework\Attributes\Depends;

class ContactTest extends BaseAmoClient
{
    protected Contact $contact;

    protected array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'Test Contact',
        ];

        $this->contact = $this->amoClient->contacts->entity();
        $this->contact->name = $this->data['name'];

        $this->amoClient->contacts->entityData($this->data);
    }

    public function test_contact_entity()
    {
        $this->assertInstanceOf(Contact::class, $this->contact);
        $this->assertEquals($this->data['name'], $this->contact->name);
    }

    #[Depends('testContactEntity')]
    public function test_contact_create()
    {
        $response = $this->contact->create();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('contacts', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['contacts']);
        $this->assertEquals(1, count($response['_embedded']['contacts']));
        $this->assertArrayHasKey('id', $response['_embedded']['contacts'][0]);

        $created = $response['_embedded']['contacts'][0];

        return $created['id'];
    }

    #[Depends('testContactCreate')]
    public function test_contact_update(int $contactId)
    {
        $newName = 'Test Contact 2';
        $this->contact->id = $contactId;
        $this->contact->name = $newName;
        $this->contact->phoneSet(['11111111111', '22222222222']);
        $this->contact->phoneAdd('3333333333');
        $this->contact->phoneAdd('4444444444');
        $this->contact->emailSet(['11111111111@example.com', '22222222222@example.com']);
        $this->contact->emailAdd('3333333333@example.com');
        $this->contact->emailAdd('4444444444@example.com');
        $this->contact->setCF(492281, 'Клиент');
        $this->contact->setCFByCode('POSITION', '222222222222222');

        $response = $this->contact->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('contacts', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['contacts']);
        $this->assertEquals(1, count($response['_embedded']['contacts']));
        $contact = $this->amoClient->contacts->find($response['_embedded']['contacts'][0]['id']);

        $this->assertInstanceOf(Carbon::class, $contact->getCreatedAt());

        $this->assertEquals($contactId, $contact->id);
        $this->assertEquals($newName, $contact->name);
        $this->assertEquals('Клиент', $contact->getCFV(492281));
        $this->assertEquals('222222222222222', $contact->getCFVByCode('POSITION'));

        $phones = $contact->phoneList();
        $this->assertContains('11111111111', $phones);
        $this->assertContains('22222222222', $phones);
        $this->assertContains('3333333333', $phones);
        $this->assertContains('4444444444', $phones);

        $emails = $contact->emailList();
        $this->assertContains('11111111111@example.com', $emails);
        $this->assertContains('22222222222@example.com', $emails);
        $this->assertContains('3333333333@example.com', $emails);
        $this->assertContains('4444444444@example.com', $emails);

        $this->contact->phoneDelete('3333333333');
        $this->contact->emailDelete('3333333333@example.com');

        $response2 = $this->contact->update();

        $this->assertIsArray($response2);
        $this->assertArrayHasKey('_embedded', $response2);
        $this->assertArrayHasKey('contacts', $response2['_embedded']);
        $this->assertIsArray($response2['_embedded']['contacts']);
        $this->assertEquals(1, count($response2['_embedded']['contacts']));
        $contact2 = $this->amoClient->contacts->find($response2['_embedded']['contacts'][0]['id']);

        $phones2 = $contact2->phoneList();

        $this->assertContains('11111111111', $phones2);
        $this->assertContains('22222222222', $phones2);
        $this->assertNotContains('3333333333', $phones2);
        $this->assertContains('4444444444', $phones2);

        $emails2 = $contact2->emailList();
        $this->assertContains('11111111111@example.com', $emails2);
        $this->assertContains('22222222222@example.com', $emails2);
        $this->assertNotContains('3333333333@example.com', $emails2);
        $this->assertContains('4444444444@example.com', $emails2);

        return $contactId;
    }

    #[Depends('testContactCreate')]
    public function test_contact_get_lead_ids(int $companyId)
    {
        $this->contact->id = $companyId;
        $leadIds = $this->contact->getLeadIds();
        $this->assertIsArray($leadIds);

        return $companyId;
    }

    #[Depends('testContactCreate')]
    public function test_contact_custom_fields(int $contactId)
    {
        $customFields = $this->amoClient->contacts->customFields()->get();
        $this->assertIsArray($customFields);
        $this->assertNotEmpty($customFields);

        return $contactId;
    }

    #[Depends('testContactCreate')]
    public function test_contact_query(int $contactId)
    {
        $query = $this->amoClient->contacts->query('Test Contact')
            ->withLeads()
            ->withCustomers()
            ->withCatalogElements()
            ->get();

        $this->assertIsArray($query);
        $this->assertNotEmpty($query);

        $this->assertArrayHasKey('_embedded', $query[0]);
        $this->assertArrayHasKey('companies', $query[0]['_embedded']);
        $this->assertArrayHasKey('customers', $query[0]['_embedded']);
        $this->assertArrayHasKey('catalog_elements', $query[0]['_embedded']);

        $query2 = $this->amoClient->contacts->query('asdfasdfasdfasdf')->get();
        $this->assertIsArray($query2);
        $this->assertEmpty($query2);

        return $contactId;
    }

    #[Depends('testContactUpdate')]
    #[Depends('testContactCustomFields')]
    #[Depends('testContactQuery')]
    public function test_contact_delete(int $contactId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/contacts/multiple/delete/', ['ID' => [$contactId]]);
        $this->assertEquals('success', $response['status']);
    }

    public function test_contact_create_get_id()
    {
        $id = $this->contact->createGetId();
        $this->assertIsInt($id);

        $response = $this->amoClient->ajax->postForm('/ajax/contacts/multiple/delete/', ['ID' => [$id]]);
        $this->assertEquals('success', $response['status']);
    }

    public function test_contact_not_found()
    {
        $response = $this->amoClient->contacts->find(112322222222222222);
        $this->assertInstanceOf(Contact::class, $this->contact);
        $this->assertIsArray($response->toArray());
        $this->assertEmpty($response->toArray());
    }

    public function test_contact_set_responsible_user()
    {
        $this->contact->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals($this->contact->responsible_user_id, 1693819);
    }

    public function test_contact_get_responsible_name()
    {
        $this->contact->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals('Кирилл Егоров', $this->contact->getResponsibleName());

        $this->contact->setResponsibleUser($this->amoClient->accountId, 0);
        $this->assertNull($this->contact->getResponsibleName());

        $this->contact->setResponsibleUser($this->amoClient->accountId, 456734556734563456);
        $this->assertNull($this->contact->getResponsibleName());
    }
}
