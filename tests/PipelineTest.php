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
            'name' => $this->marked('Test Pipeline'),
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

    #[Depends('test_pipeline_entity')]
    public function test_pipeline_create()
    {
        $response = $this->pipeline->create();
        $this->assertArrayHasKey('id', $response['_embedded']['pipelines'][0]);

        $created = $response['_embedded']['pipelines'][0];
        $this->pipeline->id = $created['id'];
        $pipelineEntityWithId = $this->amoClient->pipelines->entity($created['id']);
        $this->assertInstanceOf(Pipeline::class, $pipelineEntityWithId);

        return $this->track('pipelines', $created['id']);
    }

    public function test_pipeline_change_default_statuses()
    {

        $pipeline = $this->amoClient->pipelines->entity();
        $pipeline->name = $this->marked('testPipelineChangeSuccessStatus');
        $pipeline->sort = 10;
        $pipeline->is_main = false;
        $pipeline->addStatus('статус 1', 1, '#fffeb2');
        $pipeline->changeSuccessStatus('test_success');
        $pipeline->changeFailStatus('test_fail');
        $pipelineId = $pipeline->create()['_embedded']['pipelines'][0]['id'];
        $this->track('pipelines', $pipelineId);

        $pipeline = $this->amoClient->pipelines->find($pipelineId)->toArray();
        $statuses = $pipeline['_embedded']['statuses'];

        $this->assertEquals('test_success', $statuses[2]['name']);
        $this->assertEquals('test_fail', $statuses[3]['name']);

        /* Метод либы, не сырой ajax: тест обязан ходить тем же путём, что и потребители
         * (§9.10 ресёрча — 204 успех, 400 NotSupportedChoice «уже нет», 422 «внутри лиды»,
         * Deleter сворачивает это в bool). Снёс сам — снял с учёта: иначе реестр держит id,
         * которого уже нет, и teardown девять раз подряд пытается снести удалённое. */
        $this->assertTrue($this->amoClient->pipelines->delete($pipelineId));
        self::registry()->forget('pipelines', $pipelineId);
    }

    #[Depends('test_pipeline_create')]
    public function test_pipeline_update(int $pipelineId)
    {
        $pipeline = $this->amoClient->pipelines->entity($pipelineId);
        $newName = $this->marked('Test Pipeline2');
        $pipeline->name = $newName;
        $response = $pipeline->update();
        $this->assertEquals($newName, $response['name']);

        return $pipelineId;
    }

    #[Depends('test_pipeline_create')]
    public function test_pipeline_find(int $pipelineId)
    {
        $response = $this->amoClient->pipelines->find($pipelineId);
        $this->assertEquals($response->id, $pipelineId);
    }

    #[Depends('test_pipeline_update')]
    public function test_pipeline_delete(int $pipelineId)
    {
        $this->assertTrue($this->amoClient->pipelines->delete($pipelineId));
        self::registry()->forget('pipelines', $pipelineId);
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
