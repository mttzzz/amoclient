<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Entities\Lead;
use mttzzz\AmoClient\Entities\Task;
use PHPUnit\Framework\Attributes\Depends;

class TaskTest extends BaseAmoClient
{
    protected Task $task;

    protected Lead $lead;

    protected array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lead = $this->amoClient->leads->entity();
        $this->lead->name = 'Test Lead';
        $this->lead->price = 1000;
        $this->lead->status_id = 142;
    }

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
        $this->lead->id = $created['id'];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testTaskAdd(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->tasks->add('Test Task', null, time() + 3600, 3600, 1);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('tasks', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['tasks']);
        $this->assertEquals(1, count($response['_embedded']['tasks']));
        $this->assertArrayHasKey('id', $response['_embedded']['tasks'][0]);

        $created = $response['_embedded']['tasks'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testTaskSetResultText(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $lead->tasks->setResultText('sss');
        $response = $lead->tasks->add('Test Task', null, time() + 3600, 3600, 1);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('tasks', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['tasks']);
        $this->assertEquals(1, count($response['_embedded']['tasks']));
        $this->assertArrayHasKey('id', $response['_embedded']['tasks'][0]);

        $created = $response['_embedded']['tasks'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    #[Depends('testTaskAdd')]
    public function testLeadDelete(int $leadId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }
}
