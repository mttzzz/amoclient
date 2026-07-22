<?php

namespace mttzzz\AmoClient\Tests;

class SourceTest extends BaseAmoClient
{
    public function test_source()
    {
        $sourceEntity = $this->amoClient->sources->entity();
        $sourceEntity->name = $this->marked('test');
        /* external_id уникален в рамках аккаунта (SourceAlreadyExists) — жёсткий литерал
         * делал тест невозможным навсегда после первого невыгруженного хвоста: следующий
         * прогон падал на createGetId(), ДО собственной уборки, и починить это можно было
         * только руками в боевом аккаунте. time() — не маркер (маркер живёт в name и ищется
         * свипом), а разовость на прогон: невыгруженный хвост портит один прогон, а не все. */
        $sourceEntity->external_id = (string) time();
        $createdId = $sourceEntity->createGetId();
        $this->track('sources', $createdId);
        $found = $this->amoClient->sources->find($createdId);
        $this->assertEquals($createdId, $found->id);
        $deleted = $found->delete();
        /* delete() переведён с null на bool (lib-delete-2) — null был бы одинаково зелёным
         * и при реальном удалении, и при молчаливом no-op, bool этого различия не прячет. */
        $this->assertTrue($deleted);

    }
}
