<?php

namespace mttzzz\AmoClient\Traits;

use Illuminate\Support\Arr;

trait PhoneTrait
{
    /**
     * Get a list of phone numbers.
     *
     * @return string[] // Указываем, что массив содержит строки
     */
    public function phoneList(): array
    {
        $phones = [];
        if ($this->custom_fields_values) {
            foreach ($this->custom_fields_values as $f) {
                if (! isset($f['field_code']) || $f['field_code'] !== 'PHONE') {
                    continue;
                }

                $values = $f['values'] ?? null;
                if (! is_array($values)) {
                    continue;
                }

                foreach ($values as $v) {
                    $value = is_array($v) ? ($v['value'] ?? null) : null;
                    if (is_string($value)) {
                        $phones[] = $value;
                    }
                }
            }
        }

        return $phones;
    }

    /**
     * Get phone field data.
     *
     * @return array<int, array<string, mixed>> // Указываем, что возвращается массив, содержащий массивы с ключами строками и значениями различных типов
     */
    private function phoneGet(): array
    {
        if ($this->custom_fields_values) {
            $phones = Arr::where($this->custom_fields_values, function ($item) {
                return isset($item['field_code']) && $item['field_code'] === 'PHONE';
            });
            if (! empty($phones)) {
                return $phones;
            }
        }
        $this->custom_fields_values[] = ['field_code' => 'PHONE', 'values' => []];

        return $this->phoneGet();
    }

    /**
     * Add a phone number.
     */
    public function phoneAdd(string $phone): self
    {
        $key = key($this->phoneGet());
        if (is_int($key)) {
            $values = $this->custom_fields_values[$key]['values'] ?? null;
            $values = is_array($values) ? $values : [];
            $values[] = ['value' => $phone, 'enum_code' => 'WORK'];
            $this->custom_fields_values[$key]['values'] = $values;
        }

        return $this;
    }

    /**
     * Set multiple phone numbers.
     *
     * @param  string[]  $phones
     */
    public function phoneSet(array $phones): self
    {
        $key = key($this->phoneGet());
        $values = [];
        foreach ($phones as $phone) {
            $values[] = ['value' => $phone, 'enum_code' => 'WORK'];
        }
        if (is_int($key)) {
            $this->custom_fields_values[$key]['values'] = $values;
        }

        return $this;
    }

    /**
     * Delete a phone number.
     */
    public function phoneDelete(int $phone): self
    {
        $key = key($this->phoneGet());
        if (is_int($key)) {
            $values = $this->custom_fields_values[$key]['values'] ?? null;
            if (is_array($values)) {
                foreach ($values as $index => $value) {
                    $phoneValue = is_array($value) ? ($value['value'] ?? null) : null;
                    $phoneContact = preg_replace('/[^0-9.]+/', '', is_scalar($phoneValue) ? (string) $phoneValue : '');
                    if ((string) $phone === $phoneContact) {
                        unset($values[$index]);
                    }
                }
                $this->custom_fields_values[$key]['values'] = $values;
            }
        }

        return $this;
    }
}
