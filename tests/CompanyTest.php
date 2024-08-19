<?php

namespace mttzzz\AmoClient\Tests;

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

    }

    public function testCompanyEntity()
    {
        $this->assertInstanceOf(company::class, $this->company);
        $this->assertEquals($this->data['name'], $this->company->name);
    }

    #[Depends('testCompanyEntity')]
    public function testCompanyCreate()
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
    public function testCompanyFind(int $companyId)
    {
        $company = $this->amoClient->companies->find($companyId);
        $this->assertNotNull($company);
        $this->assertEquals($companyId, $company->id);
        $this->assertEquals($this->data['name'], $company->name);

        return $companyId;
    }

    #[Depends('testCompanyFind')]
    public function testCompanyUpdate(int $companyId)
    {
        $newName = 'Test Company 2';
        $this->company->id = $companyId;
        $this->company->name = $newName;
        $response = $this->company->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('companies', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['companies']);
        $this->assertEquals(1, count($response['_embedded']['companies']));
        $this->assertArrayHasKey('id', $response['_embedded']['companies'][0]);

        $updated = $response['_embedded']['companies'][0];

        $this->assertEquals($companyId, $updated['id']);
        $this->assertEquals($newName, $updated['name']);

        return $companyId;
    }

    #[Depends('testCompanyUpdate')]
    public function testCompanyDelete(int $companyId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/companies/multiple/delete/', ['ID' => [$companyId]]);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }
}
