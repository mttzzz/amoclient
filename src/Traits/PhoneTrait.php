<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Support\Arr;

trait PhoneTrait
{
    public function phoneList()
    {
        $phones = [];
        if ($this->custom_fields_values) {
            foreach ($this->custom_fields_values as $f) {
                if (isset($f['field_code']) && $f['field_code'] === 'PHONE') {
                    foreach ($f['values'] as $v) {
                        $phones[] = $v['value'];
                    }
                }
            }
        }
        return $phones;
    }

    private function phoneGet()
    {
        if ($this->custom_fields_values) {
            $phones = Arr::where($this->custom_fields_values, function ($item) {
                return isset($item['field_code']) && $item['field_code'] === 'PHONE';
            });
            if (!empty($phones)) {
                return $phones;
            }
        }
        $this->custom_fields_values[] = ['field_code' => 'PHONE', 'values' => []];
        return $this->phoneGet();

    }

    public function phoneAdd($phone)
    {
        $key = key($this->phoneGet());
        $this->custom_fields_values[$key]['values'][] = ['value' => $phone, 'enum_code' => 'WORK'];
        return $this;
    }

    public function phoneSet(array $phones)
    {
        $key = key($this->phoneGet());
        $values = [];
        foreach ($phones as $phone) {
            $values[] = ['value' => $phone, 'enum_code' => 'WORK'];
        }
        $this->custom_fields_values[$key]['values'] = $values;
        return $this;
    }

    public function phoneDelete(int $phone)
    {
        $key = key($this->phoneGet());
        foreach ($this->custom_fields_values[$key]['values'] as $key => $value) {
            $phoneContact = preg_replace('/[^0-9.]+/', '', $value['value']);
            if ($phone === $phoneContact) {
                unset($this->custom_fields_values[$key]['values'][$key]);
            }
        }
        return $this;
    }
}
