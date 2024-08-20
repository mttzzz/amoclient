<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Entities\Catalog;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
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

    public function testCatalogEntity()
    {
        $this->assertInstanceOf(Catalog::class, $this->catalog);
        $this->assertEquals($this->data['name'], $this->catalog->name);
        $this->assertEquals($this->data['type'], $this->catalog->type);
        $this->assertEquals($this->data['sort'], $this->catalog->sort);
        $this->assertEquals($this->data['can_add_elements'], $this->catalog->can_add_elements);
    }

    #[Depends('testCatalogEntity')]
    public function testCatalogCreate()
    {
        $response = $this->catalog->create();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('catalogs', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['catalogs']);
        $this->assertEquals(1, count($response['_embedded']['catalogs']));
        $this->assertArrayHasKey('id', $response['_embedded']['catalogs'][0]);

        $created = $response['_embedded']['catalogs'][0];

        return $created['id'];
    }

    #[Depends('testCatalogCreate')]
    public function testCatalogUpdate(int $catalogId)
    {
        $newName = 'Test Catalog2';
        $this->catalog->id = $catalogId;
        $this->catalog->name = $newName;
        $this->catalog->sort = 20;

        $response = $this->catalog->update();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('catalogs', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['catalogs']);
        $this->assertEquals(1, count($response['_embedded']['catalogs']));
        $updated = $response['_embedded']['catalogs'][0];

        $this->assertEquals($catalogId, $updated['id']);
        $this->assertEquals($newName, $updated['name']);
        $this->assertEquals(20, $updated['sort']);

        return $catalogId;
    }

    #[Depends('testCatalogUpdate')]
    public function testCatalogDelete(int $catalogId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/v1/catalogs/set/', ['request' => ['catalogs' => ['delete' => $catalogId]]]);
        $this->assertIsArray($response);
        $this->assertEquals(0, count($response['response']['catalogs']['delete']['errors']));
    }

    public function testCatalogCreateGetId()
    {
        $id = $this->catalog->createGetId();
        $this->assertIsInt($id);

        $response = $this->amoClient->ajax->postForm('/ajax/v1/catalogs/set/', ['request' => ['catalogs' => ['delete' => $id]]]);
        $this->assertIsArray($response);
        $this->assertEquals(0, count($response['response']['catalogs']['delete']['errors']));
    }

    public function testCatalogNotFound()
    {
        $response = $this->amoClient->catalogs->find(112322222222222222);
        $this->assertInstanceOf(Catalog::class, $this->catalog);
        $this->assertIsArray($response->toArray());
        $this->assertEmpty($response->toArray());
    }

    public function testCatalogCreateException()
    {
        $catalog = $this->amoClient->catalogs->entity();
        $this->expectException(AmoCustomException::class);
        $catalog->create();
    }

    public function testCatalogUpdateException()
    {
        $catalog = $this->amoClient->catalogs->entity();
        $this->expectException(AmoCustomException::class);
        $catalog->update();
    }
}
