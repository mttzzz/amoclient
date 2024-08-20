<?php

namespace mttzzz\AmoClient\Tests;

use Carbon\Carbon;
use mttzzz\AmoClient\Entities\Customer;
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

    }

    public function testCustomerEntity()
    {
        $this->assertInstanceOf(Customer::class, $this->customer);
        $this->assertEquals($this->data['name'], $this->customer->name);
    }

    #[Depends('testCustomerEntity')]
    public function testCustomerCreate()
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

    #[Depends('testCustomerCreate')]
    public function testCustomerUpdate(int $customerId)
    {
        $newName = 'Test Customer 2';
        $this->customer->id = $customerId;
        $this->customer->name = $newName;
        //TODO: Мы не синхрим поля кастомеров, поэтому функция не устанавливает значение поля
        $this->customer->setCF(475949, '111111111111');
        $this->customer->setCFByCode('POINTS', 222222222222222);

        $response = $this->customer->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('customers', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['customers']);
        $this->assertEquals(1, count($response['_embedded']['customers']));
        $customer = $this->amoClient->customers->find($response['_embedded']['customers'][0]['id']);

        $this->assertInstanceOf(Carbon::class, $customer->getCreatedAt());

        $this->assertEquals($customerId, $customer->id);
        $this->assertEquals($newName, $customer->name);
        // $this->assertEquals('111111111111', $customer->getCFV(475949));
        $this->assertEquals(222222222222222, $customer->getCFVByCode('POINTS'));

        return $customerId;
    }

    #[Depends('testCustomerUpdate')]
    public function testCustomerDelete(int $customerId)
    {
        $response = $this->amoClient->ajax->postJson('/ajax/v1/customers/set/', ['request' => ['customers' => ['delete' => [$customerId]]]]);
        $this->assertIsArray($response);
        $this->assertEquals(0, count($response['response']['customers']['delete']['errors']));
    }

    public function testCustomerCreateGetId()
    {
        $id = $this->customer->createGetId();
        $this->assertIsInt($id);

        $response = $this->amoClient->ajax->postJson('/ajax/v1/customers/set/', ['request' => ['customers' => ['delete' => [$id]]]]);
        $this->assertIsArray($response);
        $this->assertEquals(0, count($response['response']['customers']['delete']['errors']));
    }

    public function testCustomerNotFound()
    {
        $response = $this->amoClient->customers->find(112322222222222222);
        $this->assertInstanceOf(Customer::class, $this->customer);
        $this->assertIsArray($response->toArray());
        $this->assertEmpty($response->toArray());
    }

    public function testCustomerSetResponsibleUser()
    {
        $this->customer->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals($this->customer->responsible_user_id, 1693819);
    }

    public function testCustomerGetResponsibleName()
    {
        $this->customer->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals('Кирилл Егоров', $this->customer->getResponsibleName());

        $this->customer->setResponsibleUser($this->amoClient->accountId, 0);
        $this->assertNull($this->customer->getResponsibleName());

        $this->customer->setResponsibleUser($this->amoClient->accountId, 456734556734563456);
        $this->assertNull($this->customer->getResponsibleName());
    }
}
