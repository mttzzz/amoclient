<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Entities\Lead;
use mttzzz\AmoClient\Entities\Note;
use PHPUnit\Framework\Attributes\Depends;

class NoteTest extends BaseAmoClient
{
    protected Note $note;

    protected Lead $lead;

    protected array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lead = $this->amoClient->leads->entity();
        $this->lead->name = 'Test Lead';
        $this->lead->price = 1000;
        $this->lead->status_id = 142;

        $this->note = $this->lead->notes->entity();
    }

    public function testLeadCreate()
    {
        $response = $this->lead->create();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('leads', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['leads']);
        $this->assertEquals(1, count($response['_embedded']['leads']));
        $this->assertArrayHasKey('id', $response['_embedded']['leads'][0]);

        $created = $response['_embedded']['leads'][0];
        $this->lead->id = $created['id'];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testNoteCommonCreate(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->notes->entity()->common('test text');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('notes', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['notes']);
        $this->assertEquals(1, count($response['_embedded']['notes']));
        $this->assertArrayHasKey('id', $response['_embedded']['notes'][0]);

        $created = $response['_embedded']['notes'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testNoteCallInCreate(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->notes->entity()->callIn('unique_id', 120, 'http://example.com', '1234567890');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('notes', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['notes']);
        $this->assertEquals(1, count($response['_embedded']['notes']));
        $this->assertArrayHasKey('id', $response['_embedded']['notes'][0]);

        $created = $response['_embedded']['notes'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testNoteCallOutCreate(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->notes->entity()->callOut('unique_id', 120, 'http://example.com', '1234567890');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('notes', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['notes']);
        $this->assertEquals(1, count($response['_embedded']['notes']));
        $this->assertArrayHasKey('id', $response['_embedded']['notes'][0]);

        $created = $response['_embedded']['notes'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testNoteServiceMessageCreate(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->notes->entity()->serviceMessage('Текст для примечания', 'Сервис для примера');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('notes', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['notes']);
        $this->assertEquals(1, count($response['_embedded']['notes']));
        $this->assertArrayHasKey('id', $response['_embedded']['notes'][0]);

        $created = $response['_embedded']['notes'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testNoteMessageCashierCreate(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->notes->entity()->messageCashier('created', 'test text');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('notes', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['notes']);
        $this->assertEquals(1, count($response['_embedded']['notes']));
        $this->assertArrayHasKey('id', $response['_embedded']['notes'][0]);

        $created = $response['_embedded']['notes'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testNoteInvoicePaidCreate(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->notes->entity()->invoicePaid('test text', 'test service', 'http://example.com/icon.png');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('notes', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['notes']);
        $this->assertEquals(1, count($response['_embedded']['notes']));
        $this->assertArrayHasKey('id', $response['_embedded']['notes'][0]);

        $created = $response['_embedded']['notes'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testNoteGeolocationCreate(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->notes->entity()->geolocation('test text', 'test address', '123.456', '78.910');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('notes', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['notes']);
        $this->assertEquals(1, count($response['_embedded']['notes']));
        $this->assertArrayHasKey('id', $response['_embedded']['notes'][0]);

        $created = $response['_embedded']['notes'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testNoteSmsInCreate(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->notes->entity()->smsIn('test text', '1234567890');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('notes', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['notes']);
        $this->assertEquals(1, count($response['_embedded']['notes']));
        $this->assertArrayHasKey('id', $response['_embedded']['notes'][0]);

        $created = $response['_embedded']['notes'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function testNoteSmsOutCreate(int $leadId)
    {
        $lead = $this->amoClient->leads->entity($leadId);
        $response = $lead->notes->entity()->smsOut('test text', '1234567890');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertArrayHasKey('notes', $response['_embedded']);
        $this->assertIsArray($response['_embedded']['notes']);
        $this->assertEquals(1, count($response['_embedded']['notes']));
        $this->assertArrayHasKey('id', $response['_embedded']['notes'][0]);

        $created = $response['_embedded']['notes'][0];

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    #[Depends('testNoteCommonCreate')]
    #[Depends('testNoteCallInCreate')]
    #[Depends('testNoteCallOutCreate')]
    #[Depends('testNoteServiceMessageCreate')]
    #[Depends('testNoteMessageCashierCreate')]
    #[Depends('testNoteInvoicePaidCreate')]
    #[Depends('testNoteGeolocationCreate')]
    #[Depends('testNoteSmsInCreate')]
    #[Depends('testNoteSmsOutCreate')]
    public function testLeadDelete(int $leadId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }
}
