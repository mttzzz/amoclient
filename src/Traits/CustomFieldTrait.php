<?php

namespace mttzzz\AmoClient\Traits;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use mttzzz\LaravelTelegramLog\Telegram;

trait CustomFieldTrait
{
    /**
     * @var array<mixed>
     */
    protected array $cf = [];

    /**
     * @var array<mixed>
     */
    protected array $enums = [];

    public function setCFByCode(string $code, mixed $value): void
    {
        $this->custom_fields_values[] = ['field_code' => $code, 'values' => [['value' => $value]]];
    }

    /**
     * @return $this
     */
    public function setCF(int $id, mixed $value, bool $isEnumId = false): static
    {
        $values = is_array($value) ? $value : [$value];

        foreach ($values as $key => $value) {
            $values[$key] = $isEnumId ? ['enum_id' => (int) $value] : ['value' => $this->setValue($id, $value)];
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
        } elseif (is_array($this->cf) && is_string($value) && array_key_exists($id, $this->cf)) {
            $this->custom_fields_values[] = ['field_id' => $id, 'values' => $values];
        }

        return $this;
    }

    //TODO: refactor ИЗБАВИТЬСЯ ОТ MIXED
    private function setValue(int $id, mixed $value): mixed
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

    /**
     * @return array<array<string, mixed>>
     */
    public function getCF(int $id): array
    {
        return empty($this->custom_fields_values) ? [] :
            Arr::where($this->custom_fields_values, fn ($i) => isset($i['field_id']) && $i['field_id'] == $id);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getCFByCode(string $code): array
    {
        return empty($this->custom_fields_values) ? [] :
            Arr::where($this->custom_fields_values, fn ($i) => isset($i['field_code']) && $i['field_code'] == $code);
    }

    public function getCFV(int $id): mixed
    {
        return Arr::first($this->getCF($id))['values'][0]['value'] ?? null;
    }

    public function getCFVByCode(string $code): mixed
    {
        return Arr::first($this->getCFByCode(Str::upper($code)))['values'][0]['value'] ?? null;
    }

    public function getCFE(int $id): ?int
    {
        return Arr::first($this->getCF($id))['values'][0]['enum_id'] ?? null;
    }

    /**
     * @return array<string>
     */
    public function getCFCLN(int $id): array
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

    /**
     * @return array<string> // If the function returns an array of strings
     */
    public function getCFVM(int $id): array
    {
        $f = $this->getCF($id);
        /** @var array<int, array<string, mixed>> $values */
        $values = Arr::first($f)['values'] ?? [];

        return count($f) ? collect($values)->pluck('value')->toArray() : [];
    }
}
