<?php


namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

class Event extends AbstractModel
{
    protected $entity = 'events';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function id($id)
    {
        $this->filter['id'] = is_array($id) ? $id : (int)$id;
        return $this;
    }

    public function createdAt($from, $to)
    {
        $this->filter['created_at'] = [(int)$from, (int)$to];
        return $this;
    }

    /**
     * @param $createdBy
     * Макс 10 пользователей
     * @return $this
     */
    public function createdBy($createdBy)
    {
        $this->filter['created_at'] = is_array($createdBy) ? $createdBy : (int)$createdBy;
        return $this;
    }

    public function lead()
    {
        $this->filter['entity'][] = 'lead';
        return $this;
    }

    public function contact()
    {
        $this->filter['entity'][] = 'contact';
        return $this;
    }

    public function company()
    {
        $this->filter['entity'][] = 'company';
        return $this;
    }

    public function customer()
    {
        $this->filter['entity'][] = 'customer';
        return $this;
    }

    public function task()
    {
        $this->filter['entity'][] = 'task';
        return $this;
    }

    public function catalog($id)
    {
        $this->filter['entity'][] = "catalog_$id";
        return $this;
    }

    /**
     * @param $entityId int|array
     * Макс 10 ID
     * @return $this
     */
    public function entityId($entityId)
    {
        $this->filter['entity_id'] = is_array($entityId) ? $entityId : (int)$entityId;
        return $this;
    }

    public function typeLeadAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    protected function setType($function)
    {
        return Str::snake(Str::after($function, 'type'));
    }

    public function typeLeadDeleted()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeLeadRestored()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeLeadStatusChanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeLeadLinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeLeadUnlinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeContactAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeContactDeleted()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeContactRestored()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeContactLinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeContactUnlinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCompanyAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCompanyDeleted()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCompanyRestored()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCompanyLinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCompanyUnlinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCustomerAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCustomerDeleted()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCustomerStatusCanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCustomerLinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCustomerUnlinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTaskAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTaskDeleted()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTaskCompleted()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTaskTypeChanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTaskTextChanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTaskDeadlineChanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTaskResultAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeIncomingCall()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeOutgoingCall()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeIncomingChatMessage()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeOutgoingChatMessage()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeIncomingSms()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeOutgoingSms()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeEntityTagAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeEntityTagDeleted()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeEntityLinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeEntityUnlinked()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeSaleFieldChanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeNameFieldChanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeLtvFieldChanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCustomFieldValueChanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeEntityResponsibleChanged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeRobotReplied()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeIntentIdentified()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeNpsRateAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeLinkFollowed()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTransactionAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCommonNoteAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCommonNoteDeleted()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeAttachmentNoteAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTargetingInNoteAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeTargetingOutNoteAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeGeoNoteAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeServiceNoteAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeSiteVisitNoteAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeMessageToCashierNoteAdded()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeEntityMerged()
    {
        $this->filter['type'] = $this->setType(__FUNCTION__);
        return $this;
    }

    public function typeCustomFieldByIdValueChanged($fieldId)
    {
        $this->filter['type'] = "custom_field_{$fieldId}_value_changed";
        return $this;
    }

    public function valueAfterLeadStatuses($pipelineId, $statusId)
    {
        $this->filter['value_after']['leads_statuses'][] = ['pipeline_id' => $pipelineId, 'status_id' => $statusId];
        return $this;
    }

    public function valueAfterCustomerStatuses($statusId)
    {
        $this->filter['value_after']['customers_statuses'][] = ['status_id' => $statusId];
        return $this;
    }

    /**
     * @param int|array $id
     * @return $this
     */
    public function valueAfterResponsibleUserId($id)
    {
        $this->filter['value_after']['responsible_user_id'] = is_array($id) ? implode(',', $id) : (int)$id;
        return $this;
    }

    /**
     * @param int|array $value
     * @return $this
     */
    public function valueAfterCustomFieldValues($value)
    {
        $this->filter['value_after']['responsible_user_id'] = is_array($value) ? implode(',', $value) : (int)$value;
        return $this;
    }

    public function valueAfterValue(string $value)
    {
        $this->filter['value_after']['value'] = $value;
        return $this;
    }

    public function valueBeforeLeadStatuses($pipelineId, $statusId)
    {
        $this->filter['value_before']['leads_statuses'][] = ['pipeline_id' => $pipelineId, 'status_id' => $statusId];
        return $this;
    }

    public function valueBeforeCustomerStatuses($statusId)
    {
        $this->filter['value_before']['customers_statuses'][] = ['status_id' => $statusId];
        return $this;
    }

    /**
     * @param int|array $id
     * @return $this
     */
    public function valueBeforeResponsibleUserId($id)
    {
        $this->filter['value_before']['responsible_user_id'] = is_array($id) ? implode(',', $id) : (int)$id;
        return $this;
    }

    /**
     * @param int|array $value
     * @return $this
     */
    public function valueBeforeCustomFieldValues($value)
    {
        $this->filter['value_before']['responsible_user_id'] = is_array($value) ? implode(',', $value) : (int)$value;
        return $this;
    }

    public function valueBeforeValue(string $value)
    {
        $this->filter['value_before']['value'] = $value;
        return $this;
    }
}
