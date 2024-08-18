<?php

namespace mttzzz\AmoClient\Traits\Filter;

trait Common
{
    public function filterId(int $id): self
    {
        $this->filter['id'] = $id;

        return $this;
    }

    public function filterName(string $name): self
    {
        $this->filter['name'] = $name;

        return $this;
    }

    public function filterCreatedBy(int $createdBy): self
    {
        $this->filter['created_by'] = $createdBy;

        return $this;
    }

    public function filterUpdatedBy(int $updatedBy): self
    {
        $this->filter['updated_by'] = $updatedBy;

        return $this;
    }

    public function filterResponsibleUserId(int $id): self
    {
        $this->filter['responsible_user_id'] = $id;

        return $this;
    }

    public function filterCreatedAt(int $from, int $to): self
    {
        $this->filter['created_at'] = compact('from', 'to');

        return $this;
    }

    public function filterUpdatedAt(int $from, int $to): self
    {
        $this->filter['updated_at'] = compact('from', 'to');

        return $this;
    }

    public function filterClosestTaskAt(int $from, int $to): self
    {
        $this->filter['closest_task_at'] = compact('from', 'to');

        return $this;
    }

    public function filterCustomField(int $fieldId, mixed $value): self
    {
        $value = is_array($value) ? $value : [$value];
        $this->filter['custom_fields_values'][$fieldId] = $value;

        return $this;
    }

    public function filterCustomFieldFromTo(int $fieldId, int $from, int $to): self
    {
        $this->filter['custom_fields_values'][$fieldId] = compact('from', 'to');

        return $this;
    }
}
