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

    /**
     * @param  string|array<string>  $id
     */
    public function id(string|array $id): self
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
     * @param  int|array<int>  $createdBy
     *                                     Макс 10 пользователей
     */
    public function createdBy(int|array $createdBy): self
    {
        $this->filter['created_by'] = is_array($createdBy) ? $createdBy : (int) $createdBy;

        return $this;
    }

    public function lead(?int $id = null): self
    {
        $this->addFilterEntity('lead');

        return $id ? $this->entityId($id) : $this;
    }

    public function contact(?int $id = null): self
    {
        $this->addFilterEntity('contact');

        return $id ? $this->entityId($id) : $this;
    }

    public function company(?int $id = null): self
    {
        $this->addFilterEntity('company');

        return $id ? $this->entityId($id) : $this;
    }

    public function customer(?int $id = null): self
    {
        $this->addFilterEntity('customer');

        return $id ? $this->entityId($id) : $this;
    }

    public function task(?int $id = null): self
    {
        $this->addFilterEntity('task');

        return $id ? $this->entityId($id) : $this;
    }

    public function catalog(int $id, ?int $entityId = null): self
    {
        $this->addFilterEntity("catalog_$id");

        return $entityId ? $this->entityId($entityId) : $this;
    }

    /**
     * @param  int|array<int>  $entityId
     *                                    Макс 10 ID
     * @return $this
     */
    public function entityId(array|int $entityId): self
    {
        $this->filter['entity_id'] = $entityId;

        return $this;
    }

    public function typeLeadAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    protected function setType(string $function): string
    {
        return Str::snake(Str::after($function, 'type'));
    }

    public function typeLeadDeleted(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeLeadRestored(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeLeadStatusChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeLeadLinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeLeadUnlinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeContactAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeContactDeleted(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeContactRestored(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeContactLinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeContactUnlinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCompanyAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCompanyDeleted(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCompanyRestored(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCompanyLinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCompanyUnlinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCustomerAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCustomerDeleted(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCustomerStatusChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCustomerLinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCustomerUnlinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTaskAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTaskDeleted(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTaskCompleted(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTaskTypeChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTaskTextChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTaskDeadlineChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTaskResultAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeIncomingCall(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeOutgoingCall(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeIncomingChatMessage(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeOutgoingChatMessage(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeIncomingSms(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeOutgoingSms(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeEntityTagAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeEntityTagDeleted(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeEntityLinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeEntityUnlinked(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeSaleFieldChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeNameFieldChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeLtvFieldChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCustomFieldValueChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeEntityResponsibleChanged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeRobotReplied(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeIntentIdentified(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeNpsRateAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeLinkFollowed(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTransactionAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCommonNoteAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCommonNoteDeleted(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeAttachmentNoteAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTargetingInNoteAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeTargetingOutNoteAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeGeoNoteAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeServiceNoteAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeSiteVisitNoteAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeMessageToCashierNoteAdded(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeEntityMerged(): self
    {
        $this->addFilterType($this->setType(__FUNCTION__));

        return $this;
    }

    public function typeCustomFieldByIdValueChanged(int $fieldId): self
    {
        $this->addFilterType("custom_field_{$fieldId}_value_changed");

        return $this;
    }

    public function valueAfterLeadStatuses(int $pipelineId, int $statusId): self
    {
        $this->appendFilterValueItem('value_after', 'leads_statuses', ['pipeline_id' => $pipelineId, 'status_id' => $statusId]);

        return $this;
    }

    public function valueAfterCustomerStatuses(int $statusId): self
    {
        $this->appendFilterValueItem('value_after', 'customers_statuses', ['status_id' => $statusId]);

        return $this;
    }

    /**
     * @param  int|array<int>  $id
     */
    public function valueAfterResponsibleUserId(int|array $id): self
    {
        $this->setFilterValueField('value_after', 'responsible_user_id', is_array($id) ? implode(',', $id) : (int) $id);

        return $this;
    }

    /**
     * @param  int|array<int>  $value
     */
    public function valueAfterCustomFieldValues(int|array $value, int $fieldId): self
    {
        $this->setFilterValueField('value_after', 'custom_field_values', is_array($value) ? implode(',', $value) : $value);
        $this->filter['type'] = "custom_field_{$fieldId}_value_changed";

        return $this;
    }

    public function valueBeforeLeadStatuses(int $pipelineId, int $statusId): self
    {
        $this->appendFilterValueItem('value_before', 'leads_statuses', ['pipeline_id' => $pipelineId, 'status_id' => $statusId]);

        return $this;
    }

    public function valueBeforeCustomerStatuses(int $statusId): self
    {
        $this->appendFilterValueItem('value_before', 'customers_statuses', ['status_id' => $statusId]);

        return $this;
    }

    /**
     * @param  int|array<int>  $id
     */
    public function valueBeforeResponsibleUserId(int|array $id): self
    {
        $this->setFilterValueField('value_before', 'responsible_user_id', is_array($id) ? implode(',', $id) : (int) $id);

        return $this;
    }

    /**
     * @param  int|array<int>  $value
     */
    public function valueBeforeCustomFieldValues(int|array $value, int $fieldId): self
    {
        $this->setFilterValueField('value_before', 'custom_field_values', is_array($value) ? implode(',', $value) : $value);
        $this->filter['type'] = "custom_field_{$fieldId}_value_changed";

        return $this;
    }

    /**
     * $this->filter — array<string, mixed>, значение по любому ключу для
     * phpstan mixed, поэтому не пишем в него напрямую (offsetAccess на
     * mixed), а гардим is_array() перед записью и пересобираем.
     */
    private function addFilterEntity(string $value): void
    {
        $entities = $this->filter['entity'] ?? [];
        $entities = is_array($entities) ? $entities : [];
        $entities[] = $value;
        $this->filter['entity'] = $entities;
    }

    private function addFilterType(string $value): void
    {
        $types = $this->filter['type'] ?? [];
        $types = is_array($types) ? $types : [];
        $types[] = $value;
        $this->filter['type'] = $types;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function appendFilterValueItem(string $direction, string $key, array $item): void
    {
        $value = $this->filter[$direction] ?? [];
        $value = is_array($value) ? $value : [];
        $list = $value[$key] ?? [];
        $list = is_array($list) ? $list : [];
        $list[] = $item;
        $value[$key] = $list;
        $this->filter[$direction] = $value;
    }

    private function setFilterValueField(string $direction, string $key, mixed $fieldValue): void
    {
        $value = $this->filter[$direction] ?? [];
        $value = is_array($value) ? $value : [];
        $value[$key] = $fieldValue;
        $this->filter[$direction] = $value;
    }
}
