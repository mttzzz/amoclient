<?php

namespace mttzzz\AmoClient\Tests;

class TagTraitTest extends BaseAmoClient
{
    public function test_tag_single()
    {
        $tag = 'test1';
        $lead = $this->amoClient->leads->entity();
        $lead->name = $this->marked('testTagSingle');
        $lead->tag($tag);
        $leadId = $lead->createGetId();
        $this->track('leads', $leadId);
        $found = $this->amoClient->leads->find($leadId);
        $foundTags = array_column($found->toArray()['_embedded']['tags'], 'name');
        $this->assertContains($tag, $foundTags);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);
        self::registry()->forget('leads', $leadId);
    }

    public function test_tag_array()
    {
        $tags = ['test1', 'test2'];
        $lead = $this->amoClient->leads->entity();
        $lead->name = $this->marked('testTagArray');
        $lead->tag($tags);
        $leadId = $lead->createGetId();
        $this->track('leads', $leadId);
        $found = $this->amoClient->leads->find($leadId);
        $foundTags = array_column($found->toArray()['_embedded']['tags'], 'name');
        foreach ($tags as $tag) {
            $this->assertContains($tag, $foundTags);
        }

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);
        self::registry()->forget('leads', $leadId);
    }

    public function test_tag_null()
    {
        $lead = $this->amoClient->leads->entity();
        $lead->name = $this->marked('testTagNull');
        $lead->tag(null);
        $leadId = $lead->createGetId();
        $this->track('leads', $leadId);
        $found = $this->amoClient->leads->find($leadId);
        $this->assertEmpty($found->toArray()['_embedded']['tags']);

        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertEquals('success', $response['status']);
        self::registry()->forget('leads', $leadId);
    }
}
