<?php

namespace mttzzz\AmoClient\Tests;

use Carbon\Carbon;
use mttzzz\AmoClient\Entities\Lead;
use PHPUnit\Framework\Attributes\Depends;

class LeadTest extends BaseAmoClient
{
    protected Lead $lead;

    protected array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'Test Lead',
        ];

        $this->lead = $this->amoClient->leads->entity();
        $this->lead->name = $this->data['name'];
        $this->lead = $this->amoClient->leads->entityData($this->data);
    }

    public function testLeadEntity()
    {
        $this->assertInstanceOf(Lead::class, $this->lead);
        $this->assertEquals($this->data['name'], $this->lead->name);
    }

    #[Depends('testLeadEntity')]
    public function testLeadCreate()
    {
        $response = $this->lead->create();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('leads', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['leads']);
        $this->assertEquals(1, count($response['_embedded']['leads']));
        $this->assertArrayHasKey('id', $response['_embedded']['leads'][0]);

        $created = $response['_embedded']['leads'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testLeadUpdate(int $leadId)
    {
        $newName = 'Test Lead 2';
        $this->lead->id = $leadId;
        $this->lead->name = $newName;
        $this->lead->price = 1000;
        $this->lead->status_id = 142;

        $response = $this->lead->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('leads', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['leads']);
        $this->assertEquals(1, count($response['_embedded']['leads']));
        $lead = $this->amoClient->leads->find($response['_embedded']['leads'][0]['id']);

        $this->assertInstanceOf(Carbon::class, $lead->getCreatedAt());

        $this->assertEquals($leadId, $lead->id);
        $this->assertEquals($newName, $lead->name);
        $this->assertEquals(1000, $lead->price);
        $this->assertEquals(142, $lead->status_id);

        return $leadId;
    }

    #[Depends('testLeadCreate')]
    public function testLeadCustomFields(int $leadId)
    {
        $customFields = $this->amoClient->leads->customFields()->get();
        $this->assertIsArray($customFields);
        $this->assertNotEmpty($customFields);

        return $leadId;
    }

    #[Depends('testLeadCreate')]
    public function testLeadQuery(int $leadId)
    {
        $query = $this->amoClient->leads->query('Test Lead')
            ->withContacts()
            ->withCatalogElements()
            ->withLossReason()
            ->withIsPriceModifiedByRobot()
            ->withSourceId()
            ->get();

        $this->assertIsArray($query);
        $this->assertNotEmpty($query);

        $this->assertArrayHasKey('_embedded', $query[0]);
        $this->assertArrayHasKey('contacts', $query[0]['_embedded']);
        $this->assertArrayHasKey('catalog_elements', $query[0]['_embedded']);
        $this->assertArrayHasKey('loss_reason', $query[0]['_embedded']);
        $this->assertArrayHasKey('is_price_modified_by_robot', $query[0]);
        $this->assertArrayHasKey('source_id', $query[0]);

        $query2 = $this->amoClient->leads->query('asdfasdfasdfasdf')->get();
        $this->assertIsArray($query2);
        $this->assertEmpty($query2);

        $query3 = $this->amoClient->leads->query('Test Lead')->withOnlyDeleted()->get();
        $this->assertIsArray($query3);
        $this->assertEmpty($query3);

        return $leadId;
    }

    #[Depends('testLeadUpdate')]
    #[Depends('testLeadCustomFields')]
    #[Depends('testLeadQuery')]
    public function testLeadDelete(int $leadId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }

    public function testLeadCreateGetId()
    {
        $id = $this->lead->createGetId();
        $this->assertIsInt($id);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$id]]);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }

    public function testLeadNotFound()
    {
        $response = $this->amoClient->leads->find(112322222222222222);
        $this->assertInstanceOf(Lead::class, $this->lead);
        $this->assertIsArray($response->toArray());
        $this->assertEmpty($response->toArray());
    }

    public function testLeadSetResponsibleUser()
    {
        $this->lead->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals($this->lead->responsible_user_id, 1693819);
    }

    public function testLeadGetResponsibleName()
    {
        $this->lead->setResponsibleUser($this->amoClient->accountId, 1693819);
        $this->assertEquals('Кирилл Егоров', $this->lead->getResponsibleName());

        $this->lead->setResponsibleUser($this->amoClient->accountId, 0);
        $this->assertNull($this->lead->getResponsibleName());

        $this->lead->setResponsibleUser($this->amoClient->accountId, 456734556734563456);
        $this->assertNull($this->lead->getResponsibleName());
    }
}
