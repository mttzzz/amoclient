<?php

namespace mttzzz\AmoClient\Tests;

class EventTest extends BaseAmoClient
{
    public function testGetEvents()
    {
        $events = $this->amoClient->events->limit(10)->get();
        $this->assertIsArray($events);
        $this->assertGreaterThanOrEqual(0, count($events));
    }

    public function testFilterById()
    {
        $events = $this->amoClient->events->limit(1)->get();
        $this->assertIsArray($events);
        $filtered = $this->amoClient->events->id($events[0]['id'])->get();
        $this->assertEquals($events[0]['id'], $filtered[0]['id']);
    }

    public function testFilterByCreatedAt()
    {
        $createdAt = time() - 3600;
        $events = $this->amoClient->events->createdAt($createdAt, time())->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertGreaterThanOrEqual($createdAt, $event['created_at']);
        }
    }

    public function testFilterByCreatedBy()
    {
        $createdById = 1693819;
        $events = $this->amoClient->events->createdBy($createdById)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($createdById, $event['created_by']);
        }
    }

    public function testFilterByEntityLead()
    {
        $lead = $this->amoClient->leads->limit(1)->get()[0];
        $events = $this->amoClient->events->lead($lead['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($lead['id'], $event['entity_id']);
        }
    }

    public function testFilterByEntityContact()
    {
        $contact = $this->amoClient->contacts->limit(1)->get()[0];
        $events = $this->amoClient->events->contact($contact['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($contact['id'], $event['entity_id']);
        }
    }

    public function testFilterByEntityCompany()
    {
        $company = $this->amoClient->companies->limit(1)->get()[0];
        $events = $this->amoClient->events->company($company['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($company['id'], $event['entity_id']);
        }
    }

    public function testFilterByEntityCustomer()
    {
        $customer = $this->amoClient->customers->limit(1)->get()[0];
        $events = $this->amoClient->events->customer($customer['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($customer['id'], $event['entity_id']);
        }
    }

    public function testFilterByEntityTask()
    {
        $task = $this->amoClient->tasks->limit(1)->get()[0];
        $events = $this->amoClient->events->task($task['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals($task['id'], $event['entity_id']);
        }
    }

    public function testFilterByEntityCatalog()
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

    public function testFilterByTypeLeadAdded()
    {
        $events = $this->amoClient->events->typeLeadAdded()->limit(2)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('lead_added', $event['type']);
        }
    }

    public function testFilterByTypeLeadDeleted()
    {
        $events = $this->amoClient->events->typeLeadDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('lead_deleted', $event['type']);
        }
    }

    public function testFilterByTypeLeadRestored()
    {
        $events = $this->amoClient->events->typeLeadRestored()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('lead_restored', $event['type']);
        }
    }

    public function testFilterByTypeLeadStatusChanged()
    {
        $events = $this->amoClient->events->typeLeadStatusChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('lead_status_changed', $event['type']);
        }
    }

    public function testFilterByTypeLeadLinked()
    {
        $events = $this->amoClient->events->typeLeadLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
        }
    }

    public function testFilterByTypeLeadUnlinked()
    {
        $events = $this->amoClient->events->typeLeadUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
        }
    }

    public function testFilterByTypeContactAdded()
    {
        $events = $this->amoClient->events->typeContactAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('contact_added', $event['type']);
        }
    }

    public function testFilterByTypeContactDeleted()
    {
        $events = $this->amoClient->events->typeContactDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('contact_deleted', $event['type']);
        }
    }

    public function testFilterByTypeContactRestored()
    {
        $events = $this->amoClient->events->typeContactRestored()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('contact_restored', $event['type']);
        }
    }

    public function testFilterByTypeContactLinked()
    {
        $events = $this->amoClient->events->typeContactLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
        }
    }

    public function testFilterByTypeContactUnlinked()
    {
        $events = $this->amoClient->events->typeContactUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
        }
    }

    public function testFilterByTypeCompanyAdded()
    {
        $events = $this->amoClient->events->typeCompanyAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('company_added', $event['type']);
        }
    }

    public function testFilterByTypeCompanyDeleted()
    {
        $events = $this->amoClient->events->typeCompanyDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('company_deleted', $event['type']);
        }
    }

    public function testFilterByTypeCompanyRestored()
    {
        $events = $this->amoClient->events->typeCompanyRestored()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('company_restored', $event['type']);
        }
    }

    public function testFilterByTypeCompanyLinked()
    {
        $events = $this->amoClient->events->typeCompanyLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
        }
    }

    public function testFilterByTypeCompanyUnlinked()
    {
        $events = $this->amoClient->events->typeCompanyUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
        }
    }

    public function testFilterByTypeCustomerAdded()
    {
        $events = $this->amoClient->events->typeCustomerAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('customer_added', $event['type']);
        }
    }

    public function testFilterByTypeCustomerDeleted()
    {
        $events = $this->amoClient->events->typeCustomerDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('customer_deleted', $event['type']);
        }
    }

    public function testFilterByTypeCustomerStatusChanged()
    {
        $events = $this->amoClient->events->typeCustomerStatusChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('customer_status_changed', $event['type']);
        }
    }

    public function testFilterByTypeCustomerLinked()
    {
        $events = $this->amoClient->events->typeCustomerLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
            $this->assertEquals('customer', $event['entity_type']);
        }
    }

    public function testFilterByTypeCustomerUnlinked()
    {
        $events = $this->amoClient->events->typeCustomerUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
            $this->assertEquals('customer', $event['entity_type']);
        }
    }

    public function testFilterByTypeTaskAdded()
    {
        $events = $this->amoClient->events->typeTaskAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_added', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }
    }

    public function testFilterByTypeTaskDeleted()
    {
        $events = $this->amoClient->events->typeTaskDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_deleted', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }
    }

    public function testFilterByTypeTaskCompleted()
    {
        $events = $this->amoClient->events->typeTaskCompleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_completed', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }

    }

    public function testFilterByTypeTaskTypeChanged()
    {
        $events = $this->amoClient->events->typeTaskTypeChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_type_changed', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }
    }

    public function testFilterByTypeTaskTextChanged()
    {
        $events = $this->amoClient->events->typeTaskTextChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_text_changed', $event['type']);
            $this->assertEquals('task', $event['entity_type']);
        }
    }

    public function testFilterByTypeTaskDeadlineChanged()
    {
        $events = $this->amoClient->events->typeTaskDeadlineChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task_deadline_changed', $event['type']);
            $this->assertEquals('task', $event['entity_type']);

        }
    }

    public function testFilterByTypeTaskResultAdded()
    {
        $events = $this->amoClient->events->typeTaskResultAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('task', $event['entity_type']);
            $this->assertEquals('task_result_added', $event['type']);
        }
    }

    public function testFilterByTypeIncomingCall()
    {
        $events = $this->amoClient->events->typeIncomingCall()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('incoming_call', $event['type']);
        }
    }

    public function testFilterByTypeOutgoingCall()
    {
        $events = $this->amoClient->events->typeOutgoingCall()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('outgoing_call', $event['type']);
        }
    }

    public function testFilterByTypeIncomingChatMessage()
    {
        $events = $this->amoClient->events->typeIncomingChatMessage()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('incoming_chat_message', $event['type']);
        }
    }

    public function testFilterByTypeOutgoingChatMessage()
    {
        $events = $this->amoClient->events->typeOutgoingChatMessage()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('outgoing_chat_message', $event['type']);
        }
    }

    public function testFilterByTypeIncomingSms()
    {
        $events = $this->amoClient->events->typeIncomingSms()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('incoming_sms', $event['type']);
        }
    }

    public function testFilterByTypeOutgoingSms()
    {
        $events = $this->amoClient->events->typeOutgoingSms()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('outgoing_sms', $event['type']);
        }
    }

    public function testFilterByTypeEntityTagAdded()
    {
        $events = $this->amoClient->events->typeEntityTagAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_tag_added', $event['type']);
        }
    }

    public function testFilterByTypeEntityTagDeleted()
    {
        $events = $this->amoClient->events->typeEntityTagDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_tag_deleted', $event['type']);
        }

    }

    public function testFilterByTypeEntityLinked()
    {
        $events = $this->amoClient->events->typeEntityLinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_linked', $event['type']);
        }
    }

    public function testFilterByTypeEntityUnlinked()
    {
        $events = $this->amoClient->events->typeEntityUnlinked()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_unlinked', $event['type']);
        }
    }

    public function testFilterByTypeSaleFieldChanged()
    {
        $events = $this->amoClient->events->typeSaleFieldChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('sale_field_changed', $event['type']);
        }
    }

    public function testFilterByTypeNameFieldChanged()
    {
        $events = $this->amoClient->events->typeNameFieldChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('name_field_changed', $event['type']);
        }
    }

    public function testFilterByTypeLtvFieldChanged()
    {
        $events = $this->amoClient->events->typeLtvFieldChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('ltv_field_changed', $event['type']);
        }
    }

    public function testFilterByTypeCustomFieldValueChanged()
    {
        $events = $this->amoClient->events->typeCustomFieldValueChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertMatchesRegularExpression('/^custom_field_\d+_value_changed$/', $event['type']);
        }
    }

    public function testFilterByTypeEntityResponsibleChanged()
    {
        $events = $this->amoClient->events->typeEntityResponsibleChanged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_responsible_changed', $event['type']);
        }
    }

    public function testFilterByTypeRobotReplied()
    {
        $events = $this->amoClient->events->typeRobotReplied()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('robot_replied', $event['type']);
        }
    }

    public function testFilterByTypeIntentIdentified()
    {
        $events = $this->amoClient->events->typeIntentIdentified()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('intent_identified', $event['type']);
        }

    }

    public function testFilterByTypeNpsRateAdded()
    {
        $events = $this->amoClient->events->typeNpsRateAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('nps_rate_added', $event['type']);
        }
    }

    public function testFilterByTypeLinkFollowed()
    {
        $events = $this->amoClient->events->typeLinkFollowed()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('link_followed', $event['type']);
        }
    }

    public function testFilterByTypeTransactionAdded()
    {
        $events = $this->amoClient->events->typeTransactionAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('transaction_added', $event['type']);
        }
    }

    public function testFilterByTypeCommonNoteAdded()
    {
        $events = $this->amoClient->events->typeCommonNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('common_note_added', $event['type']);
        }
    }

    public function testFilterByTypeCommonNoteDeleted()
    {
        $events = $this->amoClient->events->typeCommonNoteDeleted()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('common_note_deleted', $event['type']);
        }
    }

    public function testFilterByTypeAttachmentNoteAdded()
    {
        $events = $this->amoClient->events->typeAttachmentNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('attachment_note_added', $event['type']);
        }
    }

    public function testFilterByTypeTargetingInNoteAdded()
    {
        $events = $this->amoClient->events->typeTargetingInNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('targeting_in_note_added', $event['type']);
        }
    }

    public function testFilterByTypeTargetingOutNoteAdded()
    {
        $events = $this->amoClient->events->typeTargetingOutNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('targeting_out_note_added', $event['type']);
        }
    }

    public function testFilterByTypeGeoNoteAdded()
    {
        $events = $this->amoClient->events->typeGeoNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('geo_note_added', $event['type']);
        }
    }

    public function testFilterByTypeServiceNoteAdded()
    {
        $events = $this->amoClient->events->typeServiceNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('service_note_added', $event['type']);
        }
    }

    public function testFilterByTypeSiteVisitNoteAdded()
    {
        $events = $this->amoClient->events->typeSiteVisitNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('site_visit_note_added', $event['type']);
        }
    }

    public function testFilterByTypeMessageToCashierNoteAdded()
    {
        $events = $this->amoClient->events->typeMessageToCashierNoteAdded()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('message_to_cashier_note_added', $event['type']);
        }
    }

    public function testFilterByTypeEntityMerged()
    {
        $events = $this->amoClient->events->typeEntityMerged()->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('entity_merged', $event['type']);
        }
    }

    public function testFilterByTypeCustomFieldByIdValueChanged()
    {
        $customFiled = $this->amoClient->leads->customFields()->get()[0];
        $events = $this->amoClient->events->typeCustomFieldByIdValueChanged($customFiled['id'])->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals('custom_field_'.$customFiled['id'].'_value_changed', $event['type']);
        }

    }

    public function testValueAfterLeadStatuses()
    {

        $events = $this->amoClient->events->valueAfterLeadStatuses(742990, 142)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(142, $event['value_after'][0]['lead_status']['id']);
        }

    }

    public function testValueAfterCustomerStatuses()
    {

        $events = $this->amoClient->events->valueAfterCustomerStatuses(121207)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(121207, $event['value_after'][0]['customer_status']['id']);
        }

    }

    public function testValueAfterResponsibleUserId()
    {
        $events = $this->amoClient->events->valueAfterResponsibleUserId(1693819)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(1693819, $event['value_after'][0]['responsible_user']['id']);
        }
    }

    public function testValueAfterCustomFieldValues()
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

    public function testValueBeforeLeadStatuses()
    {

        $events = $this->amoClient->events->valueBeforeLeadStatuses(742990, 142)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(142, $event['value_before'][0]['lead_status']['id']);
        }
    }

    public function testValueBeforeCustomerStatuses()
    {
        $events = $this->amoClient->events->valueBeforeCustomerStatuses(121207)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(121207, $event['value_before'][0]['customer_status']['id']);
        }
    }

    public function testValueBeforeResponsibleUserId()
    {
        $events = $this->amoClient->events->valueBeforeResponsibleUserId(1693819)->limit(10)->get();
        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertEquals(1693819, $event['value_before'][0]['responsible_user']['id']);
        }
    }

    public function testValueBeforeCustomFieldValues()
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
