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
    public function testCompanyUpdate(int $companyId)
    {
        $newName = 'Test Company 2';
        $this->company->id = $companyId;
        $this->company->name = $newName;
        $this->company->phoneAdd('11111111111');
        $this->company->phoneAdd('22222222222');
        $this->company->emailAdd('test@blalba.com');
        $this->company->emailAdd('test2@blalba.com');
        $this->company->setCF(449501, '123123123');
        $this->company->setCFByCode('ADDRESS', '123123123');

        $response = $this->company->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('companies', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['companies']);
        $this->assertEquals(1, count($response['_embedded']['companies']));
        $updated = $response['_embedded']['companies'][0];

        $this->assertEquals($companyId, $updated['id']);
        $this->assertEquals($newName, $updated['name']);

        $expectedFields = [
            'name' => $newName,
            'phones' => ['11111111111', '22222222222'],
            'emails' => ['test@blalba.com', 'test2@blalba.com'],
            'custom_fields' => [
                449501 => '123123123',
                'ADDRESS' => '123123123',
            ],
        ];

        return ['companyId' => $companyId, 'expectedFields' => $expectedFields];
    }

    #[Depends('testCompanyUpdate')]
    public function testCompanyFind(array $data)
    {
        $companyId = $data['companyId'];
        $expectedFields = $data['expectedFields'];

        $company = $this->amoClient->companies->find($companyId);
        $this->assertNotNull($company);
        $this->assertEquals($companyId, $company->id);
        $this->assertEquals($expectedFields['name'], $company->name);

        $phones = $company->phoneList();

        foreach ($expectedFields['phones'] as $expectedPhone) {
            $this->assertContains($expectedPhone, $phones);
        }

        $emails = $company->emailList();
        foreach ($expectedFields['emails'] as $expectedEmail) {
            $this->assertContains($expectedEmail, $emails);
        }

        foreach ($expectedFields['custom_fields'] as $fieldId => $expectedValue) {
            if (is_numeric($fieldId)) {
                $this->assertEquals($expectedValue, $company->getCFV($fieldId));
            } else {
                $this->assertEquals($expectedValue, $company->getCFVByCode($fieldId));
            }
        }

        return $companyId;
    }

    #[Depends('testCompanyFind')]
    public function testCompanyDelete(int $companyId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/companies/multiple/delete/', ['ID' => [$companyId]]);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }
}
