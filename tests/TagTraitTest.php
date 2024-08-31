<?php

namespace mttzzz\AmoClient\Tests;

class TagTraitTest extends BaseAmoClient
{
    public function testTagSingle()
    {
        $tag = 'test1';
        $lead = $this->amoClient->leads->entity();
        $lead->tag($tag);
        $leadId = $lead->createGetId();
        $found = $this->amoClient->leads->find($leadId);
        $foundTags = array_column($found->toArray()['_embedded']['tags'], 'name');
        $this->assertContains($tag, $foundTags);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);
    }

    public function testTagArray()
    {
        $tags = ['test1', 'test2'];
        $lead = $this->amoClient->leads->entity();
        $lead->tag($tags);
        $leadId = $lead->createGetId();
        $found = $this->amoClient->leads->find($leadId);
        $foundTags = array_column($found->toArray()['_embedded']['tags'], 'name');
        foreach ($tags as $tag) {
            $this->assertContains($tag, $foundTags);
        }

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);
    }

    public function testTagNull()
    {
        $lead = $this->amoClient->leads->entity();
        $lead->tag(null);
        $leadId = $lead->createGetId();
        $found = $this->amoClient->leads->find($leadId);
        $this->assertEmpty($found->toArray()['_embedded']['tags']);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);
    }
}
