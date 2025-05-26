<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Entities\Catalog;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Models\CustomField;
use PHPUnit\Framework\Attributes\Depends;

class CatalogTest extends BaseAmoClient
{
    protected Catalog $catalog;

    protected array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'Test Catalog',
            'type' => 'regular',
            'sort' => 10,
            'can_add_elements' => true,
            'can_link_multiple' => false,
        ];

        $this->catalog = $this->amoClient->catalogs->entity();
        $this->catalog->name = $this->data['name'];
        $this->catalog->type = $this->data['type'];
        $this->catalog->sort = $this->data['sort'];
        $this->catalog->can_add_elements = $this->data['can_add_elements'];
    }

    public function test_catalog_entity()
    {
        $this->assertInstanceOf(Catalog::class, $this->catalog);
        $this->assertEquals($this->data['name'], $this->catalog->name);
        $this->assertEquals($this->data['type'], $this->catalog->type);
        $this->assertEquals($this->data['sort'], $this->catalog->sort);
        $this->assertEquals($this->data['can_add_elements'], $this->catalog->can_add_elements);
    }

    #[Depends('testCatalogEntity')]
    public function test_catalog_create()
    {
        $response = $this->catalog->create();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('catalogs', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['catalogs']);
        $this->assertEquals(1, count($response['_embedded']['catalogs']));
        $this->assertArrayHasKey('id', $response['_embedded']['catalogs'][0]);

        $created = $response['_embedded']['catalogs'][0];
        $this->catalog->id = $created['id'];
        $catalogEntityWithId = $this->amoClient->catalogs->entity($created['id']);
        $this->assertInstanceOf(Catalog::class, $catalogEntityWithId);

        $catalogCustomFields = $catalogEntityWithId->customFields();
        $this->assertInstanceOf(CustomField::class, $catalogCustomFields);

        return $created['id'];
    }

    #[Depends('testCatalogCreate')]
    public function test_catalog_update(int $catalogId)
    {
        $newName = 'Test Catalog2';
        $this->catalog->id = $catalogId;
        $this->catalog->name = $newName;

        $response = $this->catalog->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('catalogs', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['catalogs']);
        $this->assertEquals(1, count($response['_embedded']['catalogs']));
        $updated = $response['_embedded']['catalogs'][0];

        $this->assertEquals($catalogId, $updated['id']);
        $this->assertEquals($newName, $updated['name']);

        return $catalogId;
    }

    #[Depends('testCatalogUpdate')]
    public function test_catalog_element(int $catalogId)
    {

        $catalog = $this->amoClient->catalogs->entity($catalogId);
        $catalogCustomFields = $catalog->customFields()->get();
        $elementEntity = $catalog->elements->entity();
        $elementEntity->name = 'test element';
        $elementEntity->create();

        $elementEntity2 = $catalog->elements->entityData([
            'name' => 'TestElement entityData',
        ]);
        $createdWithEntityData = $elementEntity2->create();
        $this->assertEquals($createdWithEntityData['_embedded']['elements'][0]['name'], 'TestElement entityData');

        $elements = $catalog->elements->get();

        $find = $catalog->elements->find($elements[0]['id']);
        $this->assertEquals($elements[0]['id'], $find->id);

        $filter = $catalog->elements->filterId($elements[0]['id'])->get();
        $this->assertEquals($elements[0]['id'], $filter[0]['id']);

        $elementEntity->id = $elements[0]['id'];
        $elementEntity->name = 'test 3';

        $elementEntity->setCFByCode('PRICE', 120);
        $elementEntity->update();
        $find120 = $catalog->elements->find($elements[0]['id']);
        $this->assertEquals((int) $find120->toArray()['custom_fields_values'][0]['values'][0]['value'], 120);

        return $catalogId;
    }

    #[Depends('testCatalogUpdate')]
    #[Depends('testCatalogElement')]
    public function test_catalog_delete(int $catalogId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/v1/catalogs/set/', ['request' => ['catalogs' => ['delete' => $catalogId]]]);
        $this->assertIsArray($response);
        $this->assertEquals(0, count($response['response']['catalogs']['delete']['errors']));
    }

    public function test_catalog_create_get_id()
    {
        $id = $this->catalog->createGetId();
        $this->assertIsInt($id);

        $response = $this->amoClient->ajax->postForm('/ajax/v1/catalogs/set/', ['request' => ['catalogs' => ['delete' => $id]]]);
        $this->assertIsArray($response);
        $this->assertEquals(0, count($response['response']['catalogs']['delete']['errors']));
    }

    public function test_catalog_not_found()
    {
        $response = $this->amoClient->catalogs->find(112322222222222222);
        $this->assertInstanceOf(Catalog::class, $this->catalog);
        $this->assertIsArray($response->toArray());
        $this->assertEmpty($response->toArray());
    }

    public function test_catalog_create_exception()
    {
        $catalog = $this->amoClient->catalogs->entity();
        $this->expectException(AmoCustomException::class);
        $catalog->create();
    }

    public function test_catalog_update_exception()
    {
        $catalog = $this->amoClient->catalogs->entity();
        $this->expectException(AmoCustomException::class);
        $catalog->update();
    }
}
