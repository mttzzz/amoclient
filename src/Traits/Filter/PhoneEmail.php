<?php

namespace mttzzz\AmoClient\Traits\Filter;

trait PhoneEmail
{
    protected int $fieldPhoneId;

    protected int $fieldEmailId;

    public function filterPhone(string $value): self
    {
        return $this->filterCustomField($this->fieldPhoneId, $value);
    }

    public function filterEmail(string $value): self
    {
        return $this->filterCustomField($this->fieldEmailId, $value);
    }
}
