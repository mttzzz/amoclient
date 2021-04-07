<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Support\Arr;

trait CustomFieldTrait
{
    protected $cf;

    public function setCF($id, $value, $isEnumId = false)
    {

        $values = is_array($value) ? $value : [$value];
        foreach ($values as $key => $value) {
            $values[$key] = (int)$isEnumId ? ['enum_id' => $value] : ['value' => $this->setValue($id, $value)];
        }

        if (!empty($f = $this->getCF($id))) {
            $this->custom_fields_values[array_key_first($f)]['values'] = $values;
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
                case 'numeric':
                    return (float)$value;
                case 'date_time':
                case 'date':
                    return (int)$value;
                case 'checkbox':
                    return (bool)$value;
            }
        }
        return $value;
    }

    public function getCF($id)
    {
        return empty($this->custom_fields_values) ? [] :
            Arr::where($this->custom_fields_values, fn($i) => isset($i['field_id']) && $i['field_id'] == $id);
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
