<?php

namespace mttzzz\AmoClient\Traits\Filter;

trait Common
{
    public function filterId($id)
    {
        $this->filter['id'] = $id;

        return $this;
    }

    public function filterName($name)
    {
        $this->filter['name'] = $name;

        return $this;
    }

    public function filterCreatedBy($createdBy)
    {
        $this->filter['created_by'] = $createdBy;

        return $this;
    }

    public function filterUpdatedBy($updatedBy)
    {
        $this->filter['updated_by'] = $updatedBy;

        return $this;
    }

    public function filterResponsibleUserId($id)
    {
        $this->filter['responsible_user_id'] = $id;

        return $this;
    }

    public function filterCreatedAt($from, $to)
    {
        $this->filter['created_at'] = compact('from', 'to');

        return $this;
    }

    public function filterUpdatedAt($from, $to)
    {
        $this->filter['updated_at'] = compact('from', 'to');

        return $this;
    }

    public function filterClosestTaskAt($from, $to)
    {
        $this->filter['closest_task_at'] = compact('from', 'to');

        return $this;
    }

    public function filterCustomField($fieldId, $value)
    {
        $value = is_array($value) ? $value : [$value];
        $this->filter['custom_fields_values'][$fieldId] = $value;

        return $this;
    }

    public function filterCustomFieldFromTo($fieldId, $from, $to)
    {
        $this->filter['custom_fields_values'][$fieldId] = compact('from', 'to');

        return $this;
    }
}
