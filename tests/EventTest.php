<?php

namespace mttzzz\AmoClient\Tests;

class EventTest extends BaseAmoClient
{
    public function test_get_events()
    {
        $events = $this->amoClient->events->limit(10)->get();
        $this->assertIsArray($events);
        $this->assertGreaterThanOrEqual(0, count($events));
    }

    public function test_filter_by_id()
    {
        $events = $this->amoClient->events->limit(1)->get();
        $this->assertIsArray($events);
        $filtered = $this->amoClient->events->id($events[0]['id'])->get();
        $this->assertEquals($events[0]['id'], $filtered[0]['id']);
    }

    public function test_filter_by_created_at()
    {
        $createdAt = time() - 3600;
        $events = $this->amoClient->events->createdAt($createdAt, time())->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertGreaterThanOrEqual($createdAt, $event['created_at']);
        }
    }

    public function test_filter_by_created_by()
    {
        $createdById = 1693819;
        $events = $this->amoClient->events->createdBy($createdById)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($createdById, $event['created_by']);
        }
    }

    public function test_filter_by_entity_lead()
    {
        $lead = $this->amoClient->leads->limit(1)->get()[0];
        $events = $this->amoClient->events->lead($lead['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($lead['id'], $event['entity_id']);
        }
    }

    public function test_filter_by_entity_contact()
    {
        $contact = $this->amoClient->contacts->limit(1)->get()[0];
        $events = $this->amoClient->events->contact($contact['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($contact['id'], $event['entity_id']);
        }
    }

    public function test_filter_by_entity_company()
    {
        $company = $this->amoClient->companies->limit(1)->get()[0];
        $events = $this->amoClient->events->company($company['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($company['id'], $event['entity_id']);
        }
    }

    public function test_filter_by_entity_customer()
    {
        $customer = $this->amoClient->customers->limit(1)->get()[0];
        $events = $this->amoClient->events->customer($customer['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($customer['id'], $event['entity_id']);
        }
    }

    public function test_filter_by_entity_task()
    {
        $task = $this->amoClient->tasks->limit(1)->get()[0];
        $events = $this->amoClient->events->task($task['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($task['id'], $event['entity_id']);
        }
    }

    public function test_filter_by_entity_catalog()
    {
        $catalog = $this->amoClient->catalogs->limit(1)->get()[0];
        $catalogElements = $this->amoClient->catalogs->entity($catalog['id'])->elements->get();
        $catalogElementIds = array_column($catalogElements, 'id');
        $events = $this->amoClient->events->catalog($catalog['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertContains($event['entity_id'], $catalogElementIds);
        }
    }

    public function test_filter_by_type_lead_added()
    {
        $events = $this->amoClient->events->typeLeadAdded()->limit(2)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('lead_added', $event['type']);
        }
    }

    public function test_filter_by_type_lead_deleted()
    {
        $events = $this->amoClient->events->typeLeadDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('lead_deleted', $event['type']);
        }
    }

    public function test_filter_by_type_lead_restored()
    {
        $events = $this->amoClient->events->typeLeadRestored()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('lead_restored', $event['type']);
        }
    }

    public function test_filter_by_type_lead_status_changed()
    {
        $events = $this->amoClient->events->typeLeadStatusChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('lead_status_changed', $event['type']);
        }
    }

    public function test_filter_by_type_lead_linked()
    {
        $events = $this->amoClient->events->typeLeadLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
        }
    }

    public function test_filter_by_type_lead_unlinked()
    {
        $events = $this->amoClient->events->typeLeadUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
        }
    }

    public function test_filter_by_type_contact_added()
    {
        $events = $this->amoClient->events->typeContactAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('contact_added', $event['type']);
        }
    }

    public function test_filter_by_type_contact_deleted()
    {
        $events = $this->amoClient->events->typeContactDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('contact_deleted', $event['type']);
        }
    }

    public function test_filter_by_type_contact_restored()
    {
        $events = $this->amoClient->events->typeContactRestored()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('contact_restored', $event['type']);
        }
    }

    public function test_filter_by_type_contact_linked()
    {
        $events = $this->amoClient->events->typeContactLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
        }
    }

    public function test_filter_by_type_contact_unlinked()
    {
        $events = $this->amoClient->events->typeContactUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
        }
    }

    public function test_filter_by_type_company_added()
    {
        $events = $this->amoClient->events->typeCompanyAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('company_added', $event['type']);
        }
    }

    public function test_filter_by_type_company_deleted()
    {
        $events = $this->amoClient->events->typeCompanyDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('company_deleted', $event['type']);
        }
    }

    public function test_filter_by_type_company_restored()
    {
        $events = $this->amoClient->events->typeCompanyRestored()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('company_restored', $event['type']);
        }
    }

    public function test_filter_by_type_company_linked()
    {
        $events = $this->amoClient->events->typeCompanyLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
        }
    }

    public function test_filter_by_type_company_unlinked()
    {
        $events = $this->amoClient->events->typeCompanyUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
        }
    }

    public function test_filter_by_type_customer_added()
    {
        $events = $this->amoClient->events->typeCustomerAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('customer_added', $event['type']);
        }
    }

    public function test_filter_by_type_customer_deleted()
    {
        $events = $this->amoClient->events->typeCustomerDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('customer_deleted', $event['type']);
        }
    }

    public function test_filter_by_type_customer_status_changed()
    {
        $events = $this->amoClient->events->typeCustomerStatusChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('customer_status_changed', $event['type']);
        }
    }

    public function test_filter_by_type_customer_linked()
    {
        $events = $this->amoClient->events->typeCustomerLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
            $this->assertEquals('customer', $event['entity_type']);
        }
    }

    public function test_filter_by_type_customer_unlinked()
    {
        $events = $this->amoClient->events->typeCustomerUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
            $this->assertEquals('customer', $event['entity_type']);
        }
    }

    public function test_filter_by_type_task_added()
    {
        $events = $this->amoClient->events->typeTaskAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_added', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }
    }

    public function test_filter_by_type_task_deleted()
    {
        $events = $this->amoClient->events->typeTaskDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_deleted', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }
    }

    public function test_filter_by_type_task_completed()
    {
        $events = $this->amoClient->events->typeTaskCompleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_completed', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }

    }

    public function test_filter_by_type_task_type_changed()
    {
        $events = $this->amoClient->events->typeTaskTypeChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_type_changed', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }
    }

    public function test_filter_by_type_task_text_changed()
    {
        $events = $this->amoClient->events->typeTaskTextChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_text_changed', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }
    }

    public function test_filter_by_type_task_deadline_changed()
    {
        $events = $this->amoClient->events->typeTaskDeadlineChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_deadline_changed', $event['type']);
            $this->assertEquals('task', $event['entity_type']);

        }
    }

    public function test_filter_by_type_task_result_added()
    {
        $events = $this->amoClient->events->typeTaskResultAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task', $event['entity_type']);
            $this->assertEquals('task_result_added', $event['type']);
        }
    }

    public function test_filter_by_type_incoming_call()
    {
        $events = $this->amoClient->events->typeIncomingCall()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('incoming_call', $event['type']);
        }
    }

    public function test_filter_by_type_outgoing_call()
    {
        $events = $this->amoClient->events->typeOutgoingCall()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('outgoing_call', $event['type']);
        }
    }

    public function test_filter_by_type_incoming_chat_message()
    {
        $events = $this->amoClient->events->typeIncomingChatMessage()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('incoming_chat_message', $event['type']);
        }
    }

    public function test_filter_by_type_outgoing_chat_message()
    {
        $events = $this->amoClient->events->typeOutgoingChatMessage()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('outgoing_chat_message', $event['type']);
        }
    }

    public function test_filter_by_type_incoming_sms()
    {
        $events = $this->amoClient->events->typeIncomingSms()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('incoming_sms', $event['type']);
        }
    }

    public function test_filter_by_type_outgoing_sms()
    {
        $events = $this->amoClient->events->typeOutgoingSms()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('outgoing_sms', $event['type']);
        }
    }

    public function test_filter_by_type_entity_tag_added()
    {
        $events = $this->amoClient->events->typeEntityTagAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_tag_added', $event['type']);
        }
    }

    public function test_filter_by_type_entity_tag_deleted()
    {
        $events = $this->amoClient->events->typeEntityTagDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_tag_deleted', $event['type']);
        }

    }

    public function test_filter_by_type_entity_linked()
    {
        $events = $this->amoClient->events->typeEntityLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
        }
    }

    public function test_filter_by_type_entity_unlinked()
    {
        $events = $this->amoClient->events->typeEntityUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
        }
    }

    public function test_filter_by_type_sale_field_changed()
    {
        $events = $this->amoClient->events->typeSaleFieldChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('sale_field_changed', $event['type']);
        }
    }

    public function test_filter_by_type_name_field_changed()
    {
        $events = $this->amoClient->events->typeNameFieldChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('name_field_changed', $event['type']);
        }
    }

    public function test_filter_by_type_ltv_field_changed()
    {
        $events = $this->amoClient->events->typeLtvFieldChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('ltv_field_changed', $event['type']);
        }
    }

    public function test_filter_by_type_custom_field_value_changed()
    {
        $events = $this->amoClient->events->typeCustomFieldValueChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertMatchesRegularExpression('/^custom_field_\d+_value_changed$/', $event['type']);
        }
    }

    public function test_filter_by_type_entity_responsible_changed()
    {
        $events = $this->amoClient->events->typeEntityResponsibleChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_responsible_changed', $event['type']);
        }
    }

    public function test_filter_by_type_robot_replied()
    {
        $events = $this->amoClient->events->typeRobotReplied()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('robot_replied', $event['type']);
        }
    }

    public function test_filter_by_type_intent_identified()
    {
        $events = $this->amoClient->events->typeIntentIdentified()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('intent_identified', $event['type']);
        }

    }

    public function test_filter_by_type_nps_rate_added()
    {
        $events = $this->amoClient->events->typeNpsRateAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('nps_rate_added', $event['type']);
        }
    }

    public function test_filter_by_type_link_followed()
    {
        $events = $this->amoClient->events->typeLinkFollowed()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('link_followed', $event['type']);
        }
    }

    public function test_filter_by_type_transaction_added()
    {
        $events = $this->amoClient->events->typeTransactionAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('transaction_added', $event['type']);
        }
    }

    public function test_filter_by_type_common_note_added()
    {
        $events = $this->amoClient->events->typeCommonNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('common_note_added', $event['type']);
        }
    }

    public function test_filter_by_type_common_note_deleted()
    {
        $events = $this->amoClient->events->typeCommonNoteDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('common_note_deleted', $event['type']);
        }
    }

    public function test_filter_by_type_attachment_note_added()
    {
        $events = $this->amoClient->events->typeAttachmentNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('attachment_note_added', $event['type']);
        }
    }

    public function test_filter_by_type_targeting_in_note_added()
    {
        $events = $this->amoClient->events->typeTargetingInNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('targeting_in_note_added', $event['type']);
        }
    }

    public function test_filter_by_type_targeting_out_note_added()
    {
        $events = $this->amoClient->events->typeTargetingOutNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('targeting_out_note_added', $event['type']);
        }
    }

    public function test_filter_by_type_geo_note_added()
    {
        $events = $this->amoClient->events->typeGeoNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('geo_note_added', $event['type']);
        }
    }

    public function test_filter_by_type_service_note_added()
    {
        $events = $this->amoClient->events->typeServiceNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('service_note_added', $event['type']);
        }
    }

    public function test_filter_by_type_site_visit_note_added()
    {
        $events = $this->amoClient->events->typeSiteVisitNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('site_visit_note_added', $event['type']);
        }
    }

    public function test_filter_by_type_message_to_cashier_note_added()
    {
        $events = $this->amoClient->events->typeMessageToCashierNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('message_to_cashier_note_added', $event['type']);
        }
    }

    public function test_filter_by_type_entity_merged()
    {
        $events = $this->amoClient->events->typeEntityMerged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_merged', $event['type']);
        }
    }

    public function test_filter_by_type_custom_field_by_id_value_changed()
    {
        $customFiled = $this->amoClient->leads->customFields()->get()[0];
        $events = $this->amoClient->events->typeCustomFieldByIdValueChanged($customFiled['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('custom_field_'.$customFiled['id'].'_value_changed', $event['type']);
        }

    }

    public function test_value_after_lead_statuses()
    {

        $events = $this->amoClient->events->valueAfterLeadStatuses(742990, 142)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(142, $event['value_after'][0]['lead_status']['id']);
        }

    }

    public function test_value_after_customer_statuses()
    {

        $events = $this->amoClient->events->valueAfterCustomerStatuses(121207)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(121207, $event['value_after'][0]['customer_status']['id']);
        }

    }

    public function test_value_after_responsible_user_id()
    {
        $events = $this->amoClient->events->valueAfterResponsibleUserId(1693819)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(1693819, $event['value_after'][0]['responsible_user']['id']);
        }
    }

    public function test_value_after_custom_field_values()
    {
        $customFiled = $this->amoClient->leads->customFields()->find(449487);
        $enumIds = array_column($customFiled['enums'], 'id');
        $events = $this->amoClient->events->valueAfterCustomFieldValues($enumIds, 449487)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($event['value_after'][0]['custom_field_value']['field_id'], 449487);
            $this->assertContains($event['value_after'][0]['custom_field_value']['enum_id'], $enumIds);
        }
    }

    public function test_value_before_lead_statuses()
    {

        $events = $this->amoClient->events->valueBeforeLeadStatuses(742990, 142)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(142, $event['value_before'][0]['lead_status']['id']);
        }
    }

    public function test_value_before_customer_statuses()
    {
        $events = $this->amoClient->events->valueBeforeCustomerStatuses(121207)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(121207, $event['value_before'][0]['customer_status']['id']);
        }
    }

    public function test_value_before_responsible_user_id()
    {
        $events = $this->amoClient->events->valueBeforeResponsibleUserId(1693819)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(1693819, $event['value_before'][0]['responsible_user']['id']);
        }
    }

    public function test_value_before_custom_field_values()
    {
        $customFiled = $this->amoClient->leads->customFields()->find(449487);
        $enumIds = array_column($customFiled['enums'], 'id');
        $events = $this->amoClient->events->valueBeforeCustomFieldValues($enumIds, 449487)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($event['value_before'][0]['custom_field_value']['field_id'], 449487);
            $this->assertContains($event['value_before'][0]['custom_field_value']['enum_id'], $enumIds);
        }
    }
}
