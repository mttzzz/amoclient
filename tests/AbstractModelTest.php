<?php

namespace mttzzz\AmoClient\Tests;

class AbstractModelTest extends BaseAmoClient
{
    public function testLimit()
    {
        $leads = $this->amoClient->leads->limit(10)->get();
        $this->assertEquals(10, count($leads));
    }

    public function testAllItems()
    {
        $name = uniqid('name_', true);
        $leadId = $this->amoClient->leads->entityData(['name' => $name])->createGetId();
        $leads = $this->amoClient->leads->filterName($name)->allItems();
        $this->assertEquals($name, $leads[0]['name']);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);

    }

    public function testEach()
    {
        $name = uniqid('name_', true);
        $leadId = $this->amoClient->leads->entityData(['name' => $name])->createGetId();
        $leadId2 = $this->amoClient->leads->entityData(['name' => $name])->createGetId();
        $this->amoClient->leads->filterName($name)->each(function ($chunk) use ($name) {
            $this->assertEquals($name, $chunk[0]['name']);
        }, 1);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId2]]);
        $this->assertEquals('success', $response['status']);

    }
}
