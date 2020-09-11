<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Support\Arr;

trait CustomFieldTrait
{
    protected $cf;

    public function setCF($id, $value)
    {
        $values = is_array($value) ? $value : [$value];
        foreach ($values as $key => $value) {
            $values[$key] = ['value' => $this->setValue($id, $value)];
        }

        if (!empty($f = $this->getCF($id))) {
            $this->custom_fields_values[array_key_first($f)]['values'] = $values;
        } else {
            $this->custom_fields_values[] = ['field_id' => (int)$id, 'values' => $values];
        }

        return $this;
    }

    private function setValue($id, $value)
    {
        if ($type = $this->cf[$id] ?? null) {
            switch ($type) {
                case 'textarea':
                case 'multitext':
                case 'url':
                case 'text':
                    return (string)$value;
                case 'date_time':
                case 'date':
                case 'numeric':
                case 'checkbox':
                    return (int)$value;
            }
        }
        return $value;
    }

    public function getCF($id)
    {
        return Arr::where($this->custom_fields_values, fn($i) => $i['field_id'] == $id);
    }

    public function getCFV($id)
    {
        return Arr::first($this->getCF($id))['values'][0]['value'] ?? null;
    }

    public function getCFE($id)
    {
        return Arr::first($this->getCF($id))['values'][0]['enum_id'] ?? null;
    }
}
