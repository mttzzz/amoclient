<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\AmoClientOctane;
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
    public function testTaskEntity(int $leadId)
    {

        $task = $this->amoClient->tasks->entity();
        $this->assertInstanceOf(Task::class, $task);

        return $leadId;
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

    #[Depends('testTaskAdd')]
    public function testTaskFind(int $taskId)
    {
        $foundTasks = $this->amoClient->tasks->find($taskId);
        $foundTask = $foundTasks->toArray();
        $this->assertIsArray($foundTask);
        $this->assertArrayHasKey('id', $foundTask);
        $this->assertEquals($taskId, $foundTask['id']);
    }

    #[Depends('testLeadCreate')]
    public function testTaskFilter(int $leadId)
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

        $aId = 16117840;
        $clientId = '00a140c1-7c52-4563-8b36-03f23754d255';
        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered = $this->amoClient->tasks->filterId($created['id'])->get();
        $this->assertEquals($created['id'], $filtered[0]['id']);

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered2 = $this->amoClient->tasks->filterResponsibleUserId($filtered[0]['responsible_user_id'])->get();
        $this->assertEquals($filtered[0]['responsible_user_id'], $filtered2[0]['responsible_user_id']);

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered3 = $this->amoClient->tasks->filterIsCompletedTrue()->get();
        $this->assertEquals($filtered3[0]['is_completed'], true);

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered4 = $this->amoClient->tasks->filterIsCompletedFalse()->get();
        $this->assertEquals($filtered4[0]['is_completed'], false);

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered4 = $this->amoClient->tasks->filterTaskType(1)->get();
        $this->assertEquals($filtered4[0]['task_type_id'], 1);

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered5 = $this->amoClient->tasks->filterLead(1)->get();
        $this->assertEquals($filtered5[0]['entity_type'], 'leads');

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered6 = $this->amoClient->tasks->filterContact(1)->get();
        $this->assertEquals($filtered6[0]['entity_type'], 'contacts');

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered7 = $this->amoClient->tasks->filterCompany(1)->get();
        $this->assertEquals($filtered7[0]['entity_type'], 'companies');

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $customer = $this->amoClient->customers->entityData([
            'name' => 'Test Customer',
            'next_date' => 1270000,
        ])->create();
        $this->amoClient->customers
            ->entity($customer['_embedded']['customers'][0]['id'])->tasks->add('test');

        $filtered8 = $this->amoClient->tasks->filterCustomer(1)->get();
        $this->assertEquals($filtered8[0]['entity_type'], 'customers');
        $this->amoClient->ajax->postJson('/ajax/v1/customers/set/',
            ['request' => [
                'customers' => ['delete' => [
                    $customer['_embedded']['customers'][0]['id'],
                ]],
            ]]);

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered9 = $this->amoClient->tasks->filterEntityId($leadId)->get();
        $this->assertEquals($filtered9[0]['entity_id'], $leadId);

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered10 = $this->amoClient->tasks->filterUpdatedAt(time() - 1000, time() + 1000)->get();
        $this->assertArrayHasKey('id', $filtered10[0]);

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered10Asc = $this->amoClient->tasks->orderByCompleteAsc()->get();

        // Проверка, что массив не пустой
        $this->assertNotEmpty($filtered10Asc);

        // Проверка, что массив отсортирован по complete_till в порядке возрастания
        $completeTillsAsc = array_column($filtered10Asc, 'complete_till');
        $sortedCompleteTillsAsc = $completeTillsAsc;
        sort($sortedCompleteTillsAsc, SORT_NUMERIC);

        $this->assertEquals($sortedCompleteTillsAsc, $completeTillsAsc);

        $this->amoClient = new AmoClientOctane($aId, $clientId);
        $filtered10Desc = $this->amoClient->tasks->orderByCompleteDesc()->get();

        // Проверка, что массив не пустой
        $this->assertNotEmpty($filtered10Desc);

        // Проверка, что массив отсортирован по complete_till в порядке убывания
        $completeTillsDesc = array_column($filtered10Desc, 'complete_till');
        $sortedCompleteTillsDesc = $completeTillsDesc;
        rsort($sortedCompleteTillsDesc, SORT_NUMERIC);

        $this->assertEquals($sortedCompleteTillsDesc, $completeTillsDesc);

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
