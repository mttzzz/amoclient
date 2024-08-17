<?php

namespace mttzzz\AmoClient\Traits;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use mttzzz\LaravelTelegramLog\Telegram;

trait CustomFieldTrait
{
    protected $cf;

    protected $enums;

    public function setCFByCode(string $code, $value)
    {
        $this->custom_fields_values[] = ['field_code' => $code, 'values' => [['value' => $value]]];
    }

    public function setCF(int $id, $value, bool $isEnumId = false)
    {
        $values = is_array($value) ? $value : [$value];
        foreach ($values as $key => $value) {
            $values[$key] = $isEnumId ? ['enum_id' => $value] : ['value' => $this->setValue($id, $value)];
        }

        if (isset($this->enums[$id])) {
            if (empty($values)) {
                $this->custom_fields_values[] = ['field_id' => $id, 'values' => null];
            } else {
                $enums = Arr::pluck(json_decode($this->enums[$id], true), 'value', 'id');
                if (in_array($value, $enums) || array_key_exists($value, $enums) || in_array('WORK', $enums)) {
                    $this->custom_fields_values[] = ['field_id' => $id, 'values' => $values];
                }
            }
        } elseif (is_array($this->cf) && array_key_exists($id, $this->cf)) {
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
                    return (string) $value;
                case 'url':
                    return Str::limit((string) $value, 2000, '');
                case 'text':
                    return Str::limit((string) $value, 255, '');
                case 'numeric':
                    return (float) $value;
                case 'date_time':
                case 'date':
                    try {
                        $value = strip_tags($value);

                        return is_numeric($value) ?
                            Carbon::createFromTimestamp($value)->timestamp :
                            Carbon::parseFromLocale($value, 'ru')->timestamp;
                    } catch (Exception $e) {
                        Telegram::log([
                            'value' => $value,
                            'error' => $e->getMessage(),
                        ]);

                        return null;
                    }
                case 'checkbox':
                    return (bool) $value;
                case 'birthday':
                    try {
                        $value = strip_tags($value);
                        $value = is_string($value) && ! is_numeric($value) ?
                            Carbon::parseFromLocale(str_replace('&nbsp;', ' ', $value), 'ru') :
                            Carbon::createFromTimestamp((int) $value);

                        return $value->format('Y-m-d\\TH:i:sP');
                    } catch (Exception $e) {
                        Telegram::log([
                            'value' => $value,
                            'error' => $e->getMessage(),
                        ]);

                        return '2000-01-01T00:00:00+03:00';
                    }
            }
        }

        return $value;
    }

    public function getCF($id)
    {
        return empty($this->custom_fields_values) ? [] :
            Arr::where($this->custom_fields_values, fn ($i) => isset($i['field_id']) && $i['field_id'] == $id);
    }

    public function getCFByCode($code)
    {
        return empty($this->custom_fields_values) ? [] :
            Arr::where($this->custom_fields_values, fn ($i) => isset($i['field_code']) && $i['field_code'] == $code);
    }

    public function getCFV($id)
    {
        return Arr::first($this->getCF($id))['values'][0]['value'] ?? null;
    }

    public function getCFVByCode($code)
    {
        return Arr::first($this->getCFByCode(Str::upper($code)))['values'][0]['value'] ?? null;
    }

    public function getCFE($id)
    {
        return Arr::first($this->getCF($id))['values'][0]['enum_id'] ?? null;
    }

    public function getCFCLN($id) //customField ChainedList Names
    {
        $names = [];
        $f = Arr::first($this->getCF($id));
        if ($f) {
            foreach ($f['values'] as $value) {
                $el = $this->http->get("catalogs/{$value['catalog_id']}/elements/{$value['catalog_element_id']}")->json();
                $names[] = $el['name'];
            }
        }

        return $names;
    }

    public function getCFVM($id)
    {
        $f = $this->getCF($id);

        return count($f) ? collect(Arr::first($f)['values'])->pluck('value')->toArray() : [];
    }
}
