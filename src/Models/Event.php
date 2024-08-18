<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

class Event extends AbstractModel
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'events';
    }

    public function id(int|array $id): self
    {
        $this->filter['id'] = $id;

        return $this;
    }

    public function createdAt(int $from, int $to): self
    {
        $this->filter['created_at'] = "$from,$to";

        return $this;
    }

    /**
     * @param  $createdBy
     *                    Макс 10 пользователей
     * @return $this
     */
    public function createdBy($createdBy): self
    {
        $this->filter['created_by'] = is_array($createdBy) ? $createdBy : (int) $createdBy;

        return $this;
    }

    public function lead(int|null $id = null): self
    {
        $this->filter['entity'][] = 'lead';

        return $id ? $this->entityId($id) : $this;
    }

    public function contact(int|null $id = null): self
    {
        $this->filter['entity'][] = 'contact';

        return $id ? $this->entityId($id) : $this;
    }

    public function company(int|null $id = null): self
    {
        $this->filter['entity'][] = 'company';

        return $id ? $this->entityId($id) : $this;
    }

    public function customer(int|null $id = null): self
    {
        $this->filter['entity'][] = 'customer';

        return $id ? $this->entityId($id) : $this;
    }

    public function task(int|null $id = null): self
    {
        $this->filter['entity'][] = 'task';

        return $id ? $this->entityId($id) : $this;
    }

    public function catalog(int $id, $entityId = null): self
    {
        $this->filter['entity'][] = "catalog_$id";

        return $entityId ? $this->entityId($entityId) : $this;
    }

    /**
     * @param  $entityId  int|array
     *                   Макс 10 ID
     * @return $this
     */
    public function entityId(array|int $entityId): self
    {
        $this->filter['entity_id'] = $entityId;

        return $this;
    }

    public function typeLeadAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    protected function setType(string $function): string
    {
        return Str::snake(Str::after($function, 'type'));
    }

    public function typeLeadDeleted(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeLeadRestored(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeLeadStatusChanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeLeadLinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeLeadUnlinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeContactAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeContactDeleted(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeContactRestored(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeContactLinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeContactUnlinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCompanyAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCompanyDeleted(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCompanyRestored(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCompanyLinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCompanyUnlinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCustomerAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCustomerDeleted(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCustomerStatusCanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCustomerLinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCustomerUnlinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTaskAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTaskDeleted(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTaskCompleted(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTaskTypeChanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTaskTextChanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTaskDeadlineChanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTaskResultAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeIncomingCall(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeOutgoingCall(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeIncomingChatMessage(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeOutgoingChatMessage(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeIncomingSms(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeOutgoingSms(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeEntityTagAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeEntityTagDeleted(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeEntityLinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeEntityUnlinked(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeSaleFieldChanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeNameFieldChanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeLtvFieldChanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCustomFieldValueChanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeEntityResponsibleChanged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeRobotReplied(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeIntentIdentified(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeNpsRateAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeLinkFollowed(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTransactionAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCommonNoteAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCommonNoteDeleted(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeAttachmentNoteAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTargetingInNoteAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeTargetingOutNoteAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeGeoNoteAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeServiceNoteAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeSiteVisitNoteAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeMessageToCashierNoteAdded(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeEntityMerged(): self
    {
        $this->filter['type'][] = $this->setType(__FUNCTION__);

        return $this;
    }

    public function typeCustomFieldByIdValueChanged($fieldId): self
    {
        $this->filter['type'][] = "custom_field_{$fieldId}_value_changed";

        return $this;
    }

    public function valueAfterLeadStatuses($pipelineId, $statusId): self
    {
        $this->filter['value_after']['leads_statuses'][] = ['pipeline_id' => $pipelineId, 'status_id' => $statusId];

        return $this;
    }

    public function valueAfterCustomerStatuses($statusId): self
    {
        $this->filter['value_after']['customers_statuses'][] = ['status_id' => $statusId];

        return $this;
    }

    /**
     * @param  int|array  $id
     * @return $this
     */
    public function valueAfterResponsibleUserId(int|array $id): self
    {
        $this->filter['value_after']['responsible_user_id'] = is_array($id) ? implode(',', $id) : (int) $id;

        return $this;
    }

    /**
     * @param  int|array  $value
     * @return $this
     */
    public function valueAfterCustomFieldValues(int|array $value): self
    {
        $this->filter['value_after']['responsible_user_id'] = is_array($value) ? implode(',', $value) : $value;

        return $this;
    }

    public function valueAfterValue(string $value): self
    {
        $this->filter['value_after']['value'] = $value;

        return $this;
    }

    public function valueBeforeLeadStatuses($pipelineId, $statusId): self
    {
        $this->filter['value_before']['leads_statuses'][] = ['pipeline_id' => $pipelineId, 'status_id' => $statusId];

        return $this;
    }

    public function valueBeforeCustomerStatuses($statusId): self
    {
        $this->filter['value_before']['customers_statuses'][] = ['status_id' => $statusId];

        return $this;
    }

    /**
     * @param  int|array  $id
     * @return $this
     */
    public function valueBeforeResponsibleUserId(int|array $id): array
    {
        $this->filter['value_before']['responsible_user_id'] = is_array($id) ? implode(',', $id) : (int) $id;

        return $this;
    }

    /**
     * @param  int|array  $value
     * @return $this
     */
    public function valueBeforeCustomFieldValues(int|array $value): self
    {
        $this->filter['value_before']['responsible_user_id'] = is_array($value) ? implode(',', $value) : $value;

        return $this;
    }

    public function valueBeforeValue(string $value): self
    {
        $this->filter['value_before']['value'] = $value;

        return $this;
    }
}
