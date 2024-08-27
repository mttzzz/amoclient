<?php

namespace mttzzz\AmoClient\Tests;

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
            'is_main' => true,
        ];

        $this->pipeline = $this->amoClient->pipelines->entity();
        $this->pipeline->name = $this->data['name'];
        $this->pipeline->sort = $this->data['sort'];
        $this->pipeline->is_main = $this->data['is_main'];
        $this->pipeline->addStatus('статус 1', 1, '#fffeb2');
    }

    public function testPipelineEntity()
    {
        $this->assertInstanceOf(Pipeline::class, $this->pipeline);
        $this->assertEquals($this->data['name'], $this->pipeline->name);
        $this->assertEquals($this->data['sort'], $this->pipeline->sort);
        $this->assertEquals($this->data['is_main'], $this->pipeline->is_main);

    }

    #[Depends('testPipelineEntity')]
    public function testPipelineCreate()
    {

        $response = $this->pipeline->create();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('pipelines', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['pipelines']);
        $this->assertEquals(1, count($response['_embedded']['pipelines']));
        $this->assertArrayHasKey('id', $response['_embedded']['pipelines'][0]);

        $created = $response['_embedded']['pipelines'][0];
        $this->pipeline->id = $created['id'];
        $pipelineEntityWithId = $this->amoClient->pipelines->entity($created['id']);
        $this->assertInstanceOf(Pipeline::class, $pipelineEntityWithId);

        return $created['id'];
    }

    #[Depends('testPipelineCreate')]
    public function testPipelineUpdate(int $pipelineId)
    {
        $pipeline = $this->amoClient->pipelines->entity($pipelineId);
        $newName = 'Test Pipeline2';
        $pipeline->name = $newName;
        $response = $pipeline->update();
        $this->assertEquals($newName, $response['name']);

        return $pipelineId;
    }

    #[Depends('testPipelineCreate')]
    public function testPipelineFind(int $pipelineId)
    {
        $response = $this->amoClient->pipelines->find($pipelineId);
        $this->assertEquals($response->id, $pipelineId);
    }

    #[Depends('testPipelineUpdate')]
    public function testPipelineDelete(int $pipelineId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/v1/pipelines/delete', ['request' => ['id' => $pipelineId]]);
        $this->assertIsArray($response);
        $this->assertEquals(true, $response['response'][$pipelineId]);
    }

    public function testPipelineCreateException()
    {
        $pipeline = $this->amoClient->pipelines->entity();
        $this->expectException(AmoCustomException::class);
        $pipeline->create();
    }

    public function testPipelineUpdateException()
    {
        $pipeline = $this->amoClient->pipelines->entity();
        $this->expectException(AmoCustomException::class);
        $pipeline->update();
    }
}
