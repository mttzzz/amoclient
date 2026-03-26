<?php

namespace mttzzz\AmoClient\Tests;

use Carbon\Carbon;
use mttzzz\AmoClient\Entities\Customer;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use PHPUnit\Framework\Attributes\Depends;

class CustomerTest extends BaseAmoClient
{
    protected Customer $customer;

    protected array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'Test Customer',
            'next_date' => 1270000,

        ];

        $this->customer = $this->amoClient->customers->entity();
        $this->customer->name = $this->data['name'];
        $this->customer->next_date = $this->data['next_date'];

        $this->amoClient->customers->entityData($this->data);

    }

    public function test_customer_entity()
    {
        $this->assertInstanceOf(Customer::class, $this->customer);
        $this->assertEquals($this->data['name'], $this->customer->name);
    }

    #[Depends('test_customer_entity')]
    public function test_customer_create()
    {
        $response = $this->customer->create();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('customers', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['customers']);
        $this->assertEquals(1, count($response['_embedded']['customers']));
        $this->assertArrayHasKey('id', $response['_embedded']['customers'][0]);

        $created = $response['_embedded']['customers'][0];

        return $created['id'];
    }

    #[Depends('test_customer_create')]
    public function test_customer_update(int $customerId)
    {
        $newName = 'Test Customer 2';
        $this->customer->id = $customerId;
        $this->customer->name = $newName;

        try {
            $response = $this->customer->update();
        } catch (AmoCustomException $e) {
            $this->skipIfCustomersUnavailable($e);
            $this->skipIfUnsupportedAmoResponse($e, ['Error 282.'], 'Customer update is not supported for the current account data set.');
            throw $e;
        }

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('customers', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['customers']);
        $this->assertEquals(1, count($response['_embedded']['customers']));
        try {
            $customer = $this->amoClient->customers->find($response['_embedded']['customers'][0]['id']);
        } catch (AmoCustomException $e) {
            $this->skipIfCustomersUnavailable($e);
            throw $e;
        }

        $this->assertInstanceOf(Carbon::class, $customer->getCreatedAt());

        $this->assertEquals($customerId, $customer->id);
        $this->assertEquals($newName, $customer->name);

        return $customerId;
    }

    #[Depends('test_customer_create')]
    public function test_customer_custom_fields(int $customerId)
    {
        try {
            $customFields = $this->amoClient->customers->customFields()->get();
        } catch (AmoCustomException $e) {
            $this->skipIfCustomersUnavailable($e);
            throw $e;
        }

        $this->assertIsArray($customFields);
        $this->assertNotEmpty($customFields);

        return $customerId;
    }

    #[Depends('test_customer_create')]
    public function test_customer_with(int $customerId)
    {
        try {
            $query = $this->amoClient->customers
                ->withContacts()
                ->withCompanies()
                ->withCatalogElements()
                ->get();
        } catch (AmoCustomException $e) {
            $this->skipIfCustomersUnavailable($e);
            throw $e;
        }

        $this->assertIsArray($query);
        $this->assertNotEmpty($query);

        $this->assertArrayHasKey('_embedded', $query[0]);
        $this->assertArrayHasKey('contacts', $query[0]['_embedded']);
        $this->assertArrayHasKey('companies', $query[0]['_embedded']);
        $this->assertArrayHasKey('catalog_elements', $query[0]['_embedded']);

        return $customerId;
    }

    #[Depends('test_customer_with')]
    #[Depends('test_customer_custom_fields')]
    #[Depends('test_customer_update')]
    public function test_customer_delete(int $customerId)
    {
        $response = $this->amoClient->ajax->postJson('/ajax/v1/customers/set/', ['request' => ['customers' => ['delete' => [$customerId]]]]);
        $this->assertCustomerDeleteAccepted($response);
    }

    public function test_customer_create_get_id()
    {
        $id = $this->customer->createGetId();
        $this->assertIsInt($id);

        $response = $this->amoClient->ajax->postJson('/ajax/v1/customers/set/', ['request' => ['customers' => ['delete' => [$id]]]]);
        $this->assertCustomerDeleteAccepted($response);
    }

    public function test_customer_not_found()
    {
        try {
            $response = $this->amoClient->customers->find(112322222222222222);
        } catch (AmoCustomException $e) {
            $this->skipIfCustomersUnavailable($e);
            throw $e;
        }

        $this->assertInstanceOf(Customer::class, $this->customer);
        $this->assertIsArray($response->toArray());
        $this->assertEmpty($response->toArray());
    }

    public function test_customer_set_responsible_user()
    {
        $this->customer->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals($this->customer->responsible_user_id, 1693819);
    }

    public function test_customer_get_responsible_name()
    {
        $this->customer->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals('Кирилл Егоров', $this->customer->getResponsibleName());

        $this->customer->setResponsibleUser($this->amoClient->accountId, 0);
        $this->assertNull($this->customer->getResponsibleName());

        $this->customer->setResponsibleUser($this->amoClient->accountId, 456734556734563456);
        $this->assertNull($this->customer->getResponsibleName());
    }
}
