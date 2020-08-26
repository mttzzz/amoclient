<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Support\Arr;

trait PhoneTrait
{
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
    }

    public function phoneSet(array $phones)
    {
        $key = key($this->phoneGet());
        $values = [];
        foreach ($phones as $phone) {
            $values[] = ['value' => $phone, 'enum_code' => 'WORK'];
        }
        $this->custom_fields_values[$key]['values'] = $values;
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
    }
}
