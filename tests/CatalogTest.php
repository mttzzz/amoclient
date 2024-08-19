<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Entities\Catalog;

class CatalogTest extends BaseAmoClient
{
    protected Catalog $catalog;

    protected $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'id' => 6183,
            'name' => 'Test Catalog1',
            'type' => 'regular',
            'sort' => 10,
            'can_add_elements' => true,
            'can_link_multiple' => false,
        ];

        $this->catalog = $this->amoClient->catalogs->entity();
        $this->catalog->id = $this->data['id'];
        $this->catalog->name = $this->data['name'];
        $this->catalog->type = $this->data['type'];
        $this->catalog->sort = $this->data['sort'];
        $this->catalog->can_add_elements = $this->data['can_add_elements'];
    }

    public function testCatalogEntity()
    {
        $this->assertInstanceOf(Catalog::class, $this->catalog);
        $this->assertEquals($this->data['id'], $this->catalog->id);
        $this->assertEquals($this->data['name'], $this->catalog->name);
        $this->assertEquals($this->data['type'], $this->catalog->type);
        $this->assertEquals($this->data['sort'], $this->catalog->sort);
        $this->assertEquals($this->data['can_add_elements'], $this->catalog->can_add_elements);
    }

    public function testCatalogUpdate()
    {
        $response = $this->catalog->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('catalogs', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['catalogs']);
        $this->assertEquals(1, count($response['_embedded']['catalogs']));
        $this->assertArrayHasKey('id', $response['_embedded']['catalogs'][0]);

        $updated = $response['_embedded']['catalogs'][0];

        $this->assertEquals($this->data['id'], $updated['id']);
        $this->assertEquals($this->data['name'], $updated['name']);
        $this->assertEquals($this->data['type'], $updated['type']);
        $this->assertEquals($this->data['sort'], $updated['sort']);
        $this->assertEquals($this->data['can_add_elements'], $updated['can_add_elements']);
    }

    public function testCatalogFind()
    {
        $catalog = $this->amoClient->catalogs->find($this->data['id']);
        $this->assertNotNull($catalog);
        $this->assertEquals($this->data['id'], $catalog->id);
        $this->assertEquals($this->data['name'], $catalog->name);
        $this->assertEquals($this->data['type'], $catalog->type);
        $this->assertEquals($this->data['sort'], $catalog->sort);
        $this->assertEquals($this->data['can_add_elements'], $catalog->can_add_elements);

    }
}
