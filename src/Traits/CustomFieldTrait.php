<?php


namespace mttzzz\AmoClient\Traits;


use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait CustomFieldTrait
{
    protected $cf, $enums;

    public function setCF(int $id, $value, bool $isEnumId = false)
    {

        $values = is_array($value) ? $value : [$value];
        foreach ($values as $key => $value) {
            $values[$key] = $isEnumId ? ['enum_id' => $value] : ['value' => $this->setValue($id, $value)];
        }

        if (isset($this->enums[$id])) {
            $enums = Arr::pluck(json_decode($this->enums[$id], 1), 'value', 'id');
            if (in_array($value, $enums) || key_exists($value, $enums)) {
                $this->custom_fields_values[] = ['field_id' => $id, 'values' => $values];
            }
        } else if (is_array($this->cf) && array_key_exists($id, $this->cf)) {
            $this->custom_fields_values[] = ['field_id' => $id, 'values' => $values];
        } elseif (Str::contains($this->entity, ['catalogs', 'customers'])) {
            $this->custom_fields_values[] = ['field_id' => $id, 'values' => $values];
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
                case 'birthday':
                    $value = is_string($value) ? Carbon::createFromFormat("Y-m-d", $value) : Carbon::createFromTimestamp($value);
                    return $value->format('Y-m-d\\TH:i:sP');
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
