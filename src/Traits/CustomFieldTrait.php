<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Support\Arr;

trait CustomFieldTrait
{
    public function setCF($id, $value)
    {
        $values = is_array($value) ? $value : [$value];
        foreach ($values as $key => $value) {
            $values[$key] = ['value' => $value];
        }

        if (!empty($f = $this->getCF($id))) {
            $this->custom_fields_values[array_key_first($f)]['values'] = $values;
        } else {
            $this->custom_fields_values[] = ['field_id' => $id, 'values' => $values];
        }

        return $this;
    }

    public function getCF($id)
    {
        return Arr::where($this->custom_fields_values, fn($i) => $i['field_id'] == $id);
    }

    public function getCFV($id)
    {
        return $this->getCF($id)[0]['values'][0]['value'] ?? null;
    }
}
