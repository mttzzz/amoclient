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

    public function test_lead_create()
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
    public function test_note_common_create(int $leadId)
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

        $filtered = $lead->notes->filterCommon()->filterId($created['id'])->get();
        $this->assertIsArray($filtered);
        $this->assertArrayHasKey('id', $filtered[0]);
        $this->assertEquals($created['id'], $filtered[0]['id']);

        $filtered2 = $this->amoClient->leads->entity($leadId)->notes->filterEmail()->get();
        $this->assertIsArray($filtered2);
        $this->assertEmpty($filtered2);

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function test_note_call_in_create(int $leadId)
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

        $filtered = $lead->notes->filterCallIn($created['id'])->get();
        $this->assertIsArray($filtered);
        $this->assertArrayHasKey('id', $filtered[0]);
        $this->assertEquals($created['id'], $filtered[0]['id']);

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function test_note_call_out_create(int $leadId)
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

        $filtered = $lead->notes->filterCallOut($created['id'])->get();
        $this->assertIsArray($filtered);
        $this->assertArrayHasKey('id', $filtered[0]);
        $this->assertEquals($created['id'], $filtered[0]['id']);

        return $created['id'];
    }

    #[Depends('testLeadCreate')]
    public function test_note_service_message_create(int $leadId)
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
    public function test_note_message_cashier_create(int $leadId)
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
    public function test_note_invoice_paid_create(int $leadId)
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
    public function test_note_geolocation_create(int $leadId)
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
    public function test_note_sms_in_create(int $leadId)
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
    #[Depends('testNoteCommonCreate')]
    #[Depends('testNoteCallInCreate')]
    #[Depends('testNoteCallOutCreate')]
    #[Depends('testNoteServiceMessageCreate')]
    #[Depends('testNoteMessageCashierCreate')]
    #[Depends('testNoteInvoicePaidCreate')]
    #[Depends('testNoteGeolocationCreate')]
    #[Depends('testNoteSmsInCreate')]
    public function test_note_sms_out_create(int $leadId)
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

        $filtered2 = $lead->notes->filterUpdatedAt(time() - 1000, time() + 1000)->get();
        $this->assertIsArray($filtered2);
        $this->assertArrayHasKey('id', $filtered2[0]);

        $filtered3 = $lead->notes->filterUpdatedAt(time() - 1000, time() - 900)->get();
        $this->assertIsArray($filtered3);
        $this->assertEmpty($filtered3);

        $filtered4 = $lead->notes->filterUpdatedAt(time() - 1000, time() + 1000)->orderIdAsc()->get();
        // Проверка, что массив не пустой
        $this->assertNotEmpty($filtered4);

        // Проверка, что массив отсортирован по id в порядке возрастания
        $ids = array_column($filtered4, 'id');
        $sortedIds = $ids;
        sort($sortedIds);

        $this->assertEquals($sortedIds, $ids);

        $filtered5 = $lead->notes->filterUpdatedAt(time() - 1000, time() + 1000)->orderIdDesc()->get();

        // Проверка, что массив не пустой
        $this->assertNotEmpty($filtered5);

        // Проверка, что массив отсортирован по id в порядке убывания
        $ids = array_column($filtered5, 'id');
        $sortedIds = $ids;
        rsort($sortedIds);

        $this->assertEquals($sortedIds, $ids);

        $filtered6 = $lead->notes->orderUpdatedAtAsc()->get();
        // Проверка, что массив не пустой
        $this->assertNotEmpty($filtered6);

        // Проверка, что массив отсортирован по updated_at в порядке возрастания
        $updatedAtsAsc = array_column($filtered6, 'updated_at');
        $sortedUpdatedAtsAsc = $updatedAtsAsc;
        sort($sortedUpdatedAtsAsc, SORT_NUMERIC);

        $this->assertEquals($sortedUpdatedAtsAsc, $updatedAtsAsc);

        $filtered7 = $lead->notes->orderUpdatedAtDesc()->get();

        // Проверка, что массив не пустой
        $this->assertNotEmpty($filtered7);

        // Проверка, что массив отсортирован по updated_at в порядке убывания
        $updatedAtsDesc = array_column($filtered7, 'updated_at');

        $sortedUpdatedAtsDesc = $updatedAtsDesc;
        rsort($sortedUpdatedAtsDesc, SORT_NUMERIC);

        $this->assertEquals($sortedUpdatedAtsDesc, $updatedAtsDesc);

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
    public function test_lead_delete(int $leadId)
    {
        $response = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$leadId]]);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }
}
