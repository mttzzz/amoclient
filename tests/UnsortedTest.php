<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Models\Unsorted;
use PHPUnit\Framework\Attributes\Depends;

class UnsortedTest extends BaseAmoClient
{
    public function test_create_sip_entity()
    {
        /* unsorted не в списке трекаемых типов track() и не входит в него намеренно: сам по себе
         * неудаляем публично (decline()/accept() — единственные переходы), см. §7.6 research doc.
         * Трекать здесь нечего — это не пропуск, а следствие эмпирики. */
        $sipEntity = $this->amoClient->unsorted->sip();
        $sipEntity->source_name = 'sipEntity';
        $sipEntity->source_uid = 'sipEntity';
        $sipEntity->addMetadata(rand(), rand(0, 100), 'asterisk', 'https://ya.ru', '2222222222', 0, '444444444', false);
        $created = $sipEntity->create();
        $this->assertArrayHasKey('uid', $created['_embedded']['unsorted'][0]);

        return $created;
    }

    #[Depends('test_create_sip_entity')]
    public function test_filter_uid($created)
    {
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $filterUid = $this->amoClient->unsorted->filterUid($created['_embedded']['unsorted'][0]['uid'])->get();
        $this->assertEquals($created['_embedded']['unsorted'][0]['uid'], $filterUid[0]['uid']);
    }

    #[Depends('test_create_sip_entity')]
    public function test_filter_uid_array($created)
    {
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $filterUidArray = $this->amoClient->unsorted->filterUid(['111', '222'])->get();
        $this->assertEmpty($filterUidArray);
    }

    #[Depends('test_create_sip_entity')]
    public function test_filter_category_sip($created)
    {
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $filterCategorySip = $this->amoClient->unsorted->filterCategorySip()->get();
        $this->assertEquals($created['_embedded']['unsorted'][0]['uid'], $filterCategorySip[0]['uid']);
    }

    public function test_filter_category_mail()
    {
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $filterCategoryMail = $this->amoClient->unsorted->filterCategoryMail()->get();
        $this->assertIsArray($filterCategoryMail);
        foreach ($filterCategoryMail as $item) {
            $this->assertEquals('mail', $item['category']);
        }
    }

    public function test_filter_category_chats()
    {
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $filterCategoryChats = $this->amoClient->unsorted->filterCategoryChats()->get();
        $this->assertIsArray($filterCategoryChats);
        foreach ($filterCategoryChats as $item) {
            $this->assertEquals('chats', $item['category']);
        }
    }

    #[Depends('test_create_sip_entity')]
    public function test_filter_pipeline_id($created)
    {
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $filterPipelineId = $this->amoClient->unsorted->filterPipelineId(742990)->get();
        $this->assertEquals($created['_embedded']['unsorted'][0]['uid'], $filterPipelineId[0]['uid']);
    }

    #[Depends('test_create_sip_entity')]
    #[Depends('test_filter_uid')]
    #[Depends('test_filter_uid_array')]
    #[Depends('test_filter_category_sip')]
    #[Depends('test_filter_category_mail')]
    #[Depends('test_filter_category_chats')]
    #[Depends('test_filter_pipeline_id')]
    public function test_decline($created)
    {
        /* decline() порождает лид, но он сразу лежит в корзине (is_deleted=true) — живой цикл
         * во второй волне ресёрча (§7.5, опровергает более раннюю спайк-гипотезу) показал, что
         * отдельная уборка не нужна и невозможна (повторное удаление отдаёт fail, §7.4). Ответ
         * decline() к тому же отдаёт только uid, id порождённого лида физически недоступен. */
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $declined = $this->amoClient->unsorted->decline($created['_embedded']['unsorted'][0]['uid'], 0);
        $this->assertEquals($created['_embedded']['unsorted'][0]['uid'], $declined['uid']);
    }

    #[Depends('test_decline')]
    public function test_create_and_accept_sip_entity()
    {
        $sipEntity1 = $this->amoClient->unsorted->sip();
        $sipEntity1->source_name = 'sipEntity1';
        $sipEntity1->source_uid = 'sipEntity1';
        $sipEntity1->addMetadata(rand(), rand(0, 100), 'ssssss', 'https://ya.com', '11111111111', 0, '6666666', false);

        $created1 = $sipEntity1->create();

        $sipEntity2 = $this->amoClient->unsorted->sip();
        $sipEntity2->source_name = 'sipEntity2';
        $sipEntity2->source_uid = 'sipEntity2';
        $sipEntity2->addMetadata(rand(), rand(0, 100), 'ssssss', 'https://ya.com', '22222222222', 0, '7777777', false);

        $created2 = $sipEntity2->create();

        // Проверка сортировки по created_at asc
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $sortedAsc = $this->amoClient->unsorted->orderCreatedAtAsc()->get();
        $this->assertLessThanOrEqual(
            strtotime($sortedAsc[1]['created_at']),
            strtotime($sortedAsc[0]['created_at'])
        );

        // Проверка сортировки по created_at desc
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $sortedDesc = $this->amoClient->unsorted->orderCreatedAtDesc()->get();
        $this->assertGreaterThanOrEqual(
            strtotime($sortedDesc[1]['created_at']),
            strtotime($sortedDesc[0]['created_at'])
        );

        /* accept(), в отличие от decline(), отдаёт настоящий id лида в ответе — трекаем сразу,
         * до ассертов ниже: упавший assert оставил бы созданный лид без ручной уборки (она —
         * дальше по коду, не в try/finally). */
        $accepted1 = $this->amoClient->unsorted->accept($created1['_embedded']['unsorted'][0]['uid'], 0, 16141420);
        $this->track('leads', $accepted1['_embedded']['leads'][0]['id']);
        $this->assertArrayHasKey('id', $accepted1['_embedded']['leads'][0]);
        $accepted2 = $this->amoClient->unsorted->accept($created2['_embedded']['unsorted'][0]['uid'], 0, 16141420);
        $this->track('leads', $accepted2['_embedded']['leads'][0]['id']);
        $this->assertArrayHasKey('id', $accepted2['_embedded']['leads'][0]);

        // Удаление созданных лидов
        $response1 = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$accepted1['_embedded']['leads'][0]['id']]]);
        $this->assertEquals('success', $response1['status']);

        $response2 = $this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$accepted2['_embedded']['leads'][0]['id']]]);
        $this->assertEquals('success', $response2['status']);
    }

    #[Depends('test_create_and_accept_sip_entity')]
    public function test_create_form_entity()
    {
        $formEntity = $this->amoClient->unsorted->form();
        $formEntity->source_name = 'testCreateFormEntity';
        $formEntity->source_uid = 'testCreateFormEntity';
        $formEntity->addMetadata($formEntity->source_uid, $formEntity->source_name, '111', '222', 'http://ya.ru', '127.0.0.1', 0, 'https://ya.ru');
        $created = $formEntity->create();
        $this->assertArrayHasKey('uid', $created['_embedded']['unsorted'][0]);

        return $created;
    }

    #[Depends('test_create_form_entity')]
    public function test_category_forms($created)
    {
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $filterCategoryForms = $this->amoClient->unsorted->filterCategoryForms()->get();

        $this->assertEquals($created['_embedded']['unsorted'][0]['uid'], $filterCategoryForms[0]['uid']);

        return $created;
    }

    #[Depends('test_category_forms')]
    #[Depends('test_create_form_entity')]
    public function test_decline_form($created)
    {
        /* см. комментарий в test_decline — та же эмпирика, тот же вывод: трекать нечего. */
        $this->amoClient->unsorted = new Unsorted($this->amoClient->http);
        $declined = $this->amoClient->unsorted->decline($created['_embedded']['unsorted'][0]['uid'], 0);
        $this->assertEquals($created['_embedded']['unsorted'][0]['uid'], $declined['uid']);
    }
}
