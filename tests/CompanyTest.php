<?php

namespace mttzzz\AmoClient\Tests;

use Carbon\Carbon;
use mttzzz\AmoClient\Entities\Company;
use PHPUnit\Framework\Attributes\Depends;

class CompanyTest extends BaseAmoClient
{
    protected Company $company;

    protected array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'Test Company',
        ];

        $this->company = $this->amoClient->companies->entity();
        $this->company->name = $this->data['name'];

        $this->amoClient->companies->entityData($this->data);
    }

    public function test_company_entity()
    {
        $this->assertInstanceOf(Company::class, $this->company);
        $this->assertEquals($this->data['name'], $this->company->name);
    }

    #[Depends('testCompanyEntity')]
    public function test_company_create()
    {
        $response = $this->company->create();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('companies', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['companies']);
        $this->assertEquals(1, count($response['_embedded']['companies']));
        $this->assertArrayHasKey('id', $response['_embedded']['companies'][0]);

        $created = $response['_embedded']['companies'][0];

        return $created['id'];
    }

    #[Depends('testCompanyCreate')]
    public function test_company_update(int $companyId)
    {
        $newName = 'Test Company 2';
        $this->company->id = $companyId;
        $this->company->name = $newName;
        $this->company->phoneSet(['11111111111', '22222222222']);
        $this->company->phoneAdd('3333333333');
        $this->company->phoneAdd('4444444444');
        $this->company->emailSet(['11111111111@example.com', '22222222222@example.com']);
        $this->company->emailAdd('3333333333@example.com');
        $this->company->emailAdd('4444444444@example.com');
        $this->company->setCF(449501, '111111111111');
        $this->company->setCFByCode('ADDRESS', '222222222222222');
        $response = $this->company->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('companies', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['companies']);
        $this->assertEquals(1, count($response['_embedded']['companies']));
        $company = $this->amoClient->companies->find($response['_embedded']['companies'][0]['id']);

        $this->assertInstanceOf(Carbon::class, $company->getCreatedAt());

        $this->assertEquals($companyId, $company->id);
        $this->assertEquals($newName, $company->name);
        $this->assertEquals('111111111111', $company->getCFV(449501));
        $this->assertEquals('222222222222222', $company->getCFVByCode('ADDRESS'));

        $phones = $company->phoneList();
        $this->assertContains('11111111111', $phones);
        $this->assertContains('22222222222', $phones);
        $this->assertContains('3333333333', $phones);
        $this->assertContains('4444444444', $phones);

        $emails = $company->emailList();
        $this->assertContains('11111111111@example.com', $emails);
        $this->assertContains('22222222222@example.com', $emails);
        $this->assertContains('3333333333@example.com', $emails);
        $this->assertContains('4444444444@example.com', $emails);

        $this->company->phoneDelete('3333333333');
        $this->company->emailDelete('3333333333@example.com');

        $response2 = $this->company->update();

        $this->assertIsArray($response2);
        $this->assertArrayHasKey('_embedded', $response2);
        $this->assertArrayHasKey('companies', $response2['_embedded']);
        $this->assertIsArray($response2['_embedded']['companies']);
        $this->assertEquals(1, count($response2['_embedded']['companies']));
        $company2 = $this->amoClient->companies->find($response2['_embedded']['companies'][0]['id']);

        $phones2 = $company2->phoneList();

        $this->assertContains('11111111111', $phones2);
        $this->assertContains('22222222222', $phones2);
        $this->assertNotContains('3333333333', $phones2);
        $this->assertContains('4444444444', $phones2);

        $emails2 = $company2->emailList();
        $this->assertContains('11111111111@example.com', $emails2);
        $this->assertContains('22222222222@example.com', $emails2);
        $this->assertNotContains('3333333333@example.com', $emails2);
        $this->assertContains('4444444444@example.com', $emails2);

        return $companyId;
    }

    #[Depends('testCompanyCreate')]
    public function test_company_get_lead_ids(int $companyId)
    {
        $this->company->id = $companyId;
        $leadIds = $this->company->getLeadIds();
        $this->assertIsArray($leadIds);

        return $companyId;
    }

    #[Depends('testCompanyCreate')]
    public function test_company_custom_fields(int $companyId)
    {
        $customFields = $this->amoClient->companies->customFields()->get();
        $this->assertIsArray($customFields);
        $this->assertNotEmpty($customFields);

        return $companyId;
    }

    #[Depends('testCompanyCreate')]
    public function test_company_query(int $companyId)
    {
        $query = $this->amoClient->companies->query('Test Company')
            ->withContacts()
            ->withCustomers()
            ->withContacts()
            ->withCatalogElements()
            ->withLeads()
            ->get();

        $this->assertIsArray($query);
        $this->assertNotEmpty($query);

        $this->assertArrayHasKey('_embedded', $query[0]);
        $this->assertArrayHasKey('customers', $query[0]['_embedded']);
        $this->assertArrayHasKey('contacts', $query[0]['_embedded']);
        $this->assertArrayHasKey('leads', $query[0]['_embedded']);
        $this->assertArrayHasKey('catalog_elements', $query[0]['_embedded']);

        $query2 = $this->amoClient->companies->query('asdfasdfasdfasdf')->get();
        $this->assertIsArray($query2);
        $this->assertEmpty($query2);

        return $companyId;

    }

    #[Depends('testCompanyUpdate')]
    #[Depends('testCompanyQuery')]
    #[Depends('testCompanyCustomFields')]
    public function test_company_delete(int $companyId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/companies/multiple/delete/', ['ID' => [$companyId]]);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }

    public function test_company_create_get_id()
    {
        $id = $this->company->createGetId();
        $this->assertIsInt($id);

        $response = $this->amoClient->ajax->postForm('/ajax/companies/multiple/delete/', ['ID' => [$id]]);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }

    public function test_company_not_found()
    {
        $response = $this->amoClient->companies->find(112322222222222222);
        $this->assertInstanceOf(Company::class, $this->company);
        $this->assertIsArray($response->toArray());
        $this->assertEmpty($response->toArray());
    }

    public function test_company_set_responsible_user()
    {
        $this->company->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals($this->company->responsible_user_id, 1693819);
    }

    public function test_company_get_responsible_name()
    {
        $this->company->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals('Кирилл Егоров', $this->company->getResponsibleName());

        $this->company->setResponsibleUser($this->amoClient->accountId, 0);
        $this->assertNull($this->company->getResponsibleName());

        $this->company->setResponsibleUser($this->amoClient->accountId, 456734556734563456);
        $this->assertNull($this->company->getResponsibleName());

    }
}
