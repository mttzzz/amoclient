<?php

namespace mttzzz\AmoClient\Traits;

use Illuminate\Support\Arr;

trait EmailTrait
{
    public function emailList(): array
    {
        $emails = [];
        if ($this->custom_fields_values) {
            foreach ($this->custom_fields_values as $f) {
                if (isset($f['field_code']) && $f['field_code'] === 'EMAIL') {
                    foreach ($f['values'] as $v) {
                        $emails[] = $v['value'];
                    }
                }
            }
        }

        return $emails;
    }

    private function emailGet()
    {
        if ($this->custom_fields_values) {
            $emails = Arr::where($this->custom_fields_values, function ($item) {
                return isset($item['field_code']) && $item['field_code'] === 'EMAIL';
            });
            if (! empty($emails)) {
                return $emails;
            }
        }
        $this->custom_fields_values[] = ['field_code' => 'EMAIL', 'values' => []];

        return $this->emailGet();

    }

    public function emailAdd($email): self
    {
        $key = key($this->emailGet());
        $this->custom_fields_values[$key]['values'][] = ['value' => $email, 'enum_code' => 'WORK'];

        return $this;
    }

    public function emailSet(array $emails): self
    {
        $key = key($this->emailGet());
        $values = [];
        foreach ($emails as $email) {
            $values[] = ['value' => $email, 'enum_code' => 'WORK'];
        }
        $this->custom_fields_values[$key]['values'] = $values;

        return $this;
    }

    public function emailDelete(string $email): self
    {
        $key = key($this->emailGet());
        foreach ($this->custom_fields_values[$key]['values'] as $key => $value) {
            if ($email === $value['value']) {
                unset($this->custom_fields_values[$key]['values'][$key]);
            }
        }

        return $this;
    }
}
