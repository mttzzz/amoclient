<?php

namespace mttzzz\AmoClient\Traits\Filter;

trait PhoneEmail
{
    protected $fieldPhoneId, $fieldEmailId;

    public function filterPhone($value)
    {
        return $this->filterCustomField($this->fieldPhoneId, $value);
    }

    public function filterEmail($value)
    {
        return $this->filterCustomField($this->fieldEmailId, $value);
    }
}
