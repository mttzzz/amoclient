<?php

namespace mttzzz\AmoClient\Tests;

use Illuminate\Support\Collection;
use mttzzz\AmoClient\Entities\Pipeline;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use PHPUnit\Framework\Attributes\Depends;

class PipelineTest extends BaseAmoClient
{
    protected Pipeline $pipeline;

    protected array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'Test Pipeline',
            'sort' => 10,
            'is_main' => false,
        ];

        $this->pipeline = $this->amoClient->pipelines->entity();
        $this->pipeline->name = $this->data['name'];
        $this->pipeline->sort = $this->data['sort'];
        $this->pipeline->is_main = $this->data['is_main'];
        $this->pipeline->addStatus('статус 1', 1, '#fffeb2');
    }

    public function test_pipeline_entity()
    {
        $this->assertInstanceOf(Pipeline::class, $this->pipeline);
        $this->assertEquals($this->data['name'], $this->pipeline->name);
        $this->assertEquals($this->data['sort'], $this->pipeline->sort);
        $this->assertEquals($this->data['is_main'], $this->pipeline->is_main);

    }

    #[Depends('testPipelineEntity')]
    public function test_pipeline_create()
    {
        $response = $this->pipeline->create();
        $this->assertArrayHasKey('id', $response['_embedded']['pipelines'][0]);

        $created = $response['_embedded']['pipelines'][0];
        $this->pipeline->id = $created['id'];
        $pipelineEntityWithId = $this->amoClient->pipelines->entity($created['id']);
        $this->assertInstanceOf(Pipeline::class, $pipelineEntityWithId);

        return $created['id'];
    }

    public function test_pipeline_change_default_statuses()
    {

        $pipeline = $this->amoClient->pipelines->entity();
        $pipeline->name = 'testPipelineChangeSuccessStatus';
        $pipeline->sort = 10;
        $pipeline->is_main = false;
        $pipeline->addStatus('статус 1', 1, '#fffeb2');
        $pipeline->changeSuccessStatus('test_success');
        $pipeline->changeFailStatus('test_fail');
        $pipelineId = $pipeline->create()['_embedded']['pipelines'][0]['id'];

        $pipeline = $this->amoClient->pipelines->find($pipelineId)->toArray();
        $statuses = $pipeline['_embedded']['statuses'];

        $this->assertEquals('test_success', $statuses[2]['name']);
        $this->assertEquals('test_fail', $statuses[3]['name']);

        $response = $this->amoClient->ajax->postForm('/ajax/v1/pipelines/delete', ['request' => ['id' => $pipelineId]]);
        $this->assertEquals(true, $response['response'][$pipelineId]);

    }

    #[Depends('testPipelineCreate')]
    public function test_pipeline_update(int $pipelineId)
    {
        $pipeline = $this->amoClient->pipelines->entity($pipelineId);
        $newName = 'Test Pipeline2';
        $pipeline->name = $newName;
        $response = $pipeline->update();
        $this->assertEquals($newName, $response['name']);

        return $pipelineId;
    }

    #[Depends('testPipelineCreate')]
    public function test_pipeline_find(int $pipelineId)
    {
        $response = $this->amoClient->pipelines->find($pipelineId);
        $this->assertEquals($response->id, $pipelineId);
    }

    #[Depends('testPipelineUpdate')]
    public function test_pipeline_delete(int $pipelineId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/v1/pipelines/delete', ['request' => ['id' => $pipelineId]]);
        $this->assertEquals(true, $response['response'][$pipelineId]);
    }

    public function test_pipeline_create_exception()
    {
        $pipeline = $this->amoClient->pipelines->entity();
        $this->expectException(AmoCustomException::class);
        $pipeline->create();
    }

    public function test_pipeline_update_exception()
    {
        $pipeline = $this->amoClient->pipelines->entity();
        $this->expectException(AmoCustomException::class);
        $pipeline->update();
    }

    public function test_pipeline_statuses()
    {
        $pipeline = $this->amoClient->pipelines->entity();
        $this->assertInstanceOf(Collection::class, $pipeline->statuses());
        $this->assertEmpty($pipeline->statuses());

        $pipeline2 = $this->amoClient->pipelines->entity();
        $pipeline2->addStatus('статус 1', 1, '#fffeb2');
        $pipeline2->statuses();
        $this->assertInstanceOf(Collection::class, $pipeline2->statuses());
        $this->assertEquals(1, $pipeline2->statuses()->count());

    }
}
