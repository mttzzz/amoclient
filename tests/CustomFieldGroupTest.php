<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Entities\CustomFieldGroup;
use mttzzz\AmoClient\Models\CustomFieldGroup as CustomFieldGroupModel;

class CustomFieldGroupTest extends BaseAmoClient
{
    protected CustomFieldGroupModel $customFieldGroup;

    protected function setUp(): void
    {
        parent::setUp();

        // Инициализируем CustomFieldGroup с реальным клиентом
        $this->customFieldGroup = new CustomFieldGroupModel($this->amoClient->http, 'leads');
    }

    public function testCustomFieldGroupEntity()
    {
        $customFieldGroupEntity = $this->customFieldGroup->entity(123);

        $this->assertInstanceOf(CustomFieldGroup::class, $customFieldGroupEntity);
        $this->assertEquals(123, $customFieldGroupEntity->id);
    }
}
