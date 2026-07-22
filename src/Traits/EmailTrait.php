<?php

namespace mttzzz\AmoClient\Traits;

use Illuminate\Support\Arr;

trait EmailTrait
{
    /**
     * @return array<string>
     */
    public function emailList(): array
    {
        $emails = [];
        if ($this->custom_fields_values) {
            foreach ($this->custom_fields_values as $f) {
                if (! isset($f['field_code']) || $f['field_code'] !== 'EMAIL') {
                    continue;
                }

                $values = $f['values'] ?? null;
                if (! is_array($values)) {
                    continue;
                }

                foreach ($values as $v) {
                    $value = is_array($v) ? ($v['value'] ?? null) : null;
                    if (is_string($value)) {
                        $emails[] = $value;
                    }
                }
            }
        }

        return $emails;
    }

    /**
     * @return array<mixed>
     */
    private function emailGet(): array
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

    public function emailAdd(string $email): self
    {
        $key = key($this->emailGet());
        if (is_int($key)) {
            $values = $this->custom_fields_values[$key]['values'] ?? null;
            $values = is_array($values) ? $values : [];
            $values[] = ['value' => $email, 'enum_code' => 'WORK'];
            $this->custom_fields_values[$key]['values'] = $values;
        }

        return $this;
    }

    /**
     * @param  array<string>  $emails
     */
    public function emailSet(array $emails): self
    {
        $key = key($this->emailGet());
        $values = [];
        foreach ($emails as $email) {
            $values[] = ['value' => $email, 'enum_code' => 'WORK'];
        }
        if (is_int($key)) {
            $this->custom_fields_values[$key]['values'] = $values;
        }

        return $this;
    }

    public function emailDelete(string $email): self
    {
        $key = key($this->emailGet());
        if (is_int($key)) {
            $values = $this->custom_fields_values[$key]['values'] ?? null;
            if (is_array($values)) {
                foreach ($values as $index => $value) {
                    $emailValue = is_array($value) ? ($value['value'] ?? null) : null;
                    if ($email === $emailValue) {
                        unset($values[$index]);
                    }
                }
                $this->custom_fields_values[$key]['values'] = $values;
            }
        }

        return $this;
    }
}
