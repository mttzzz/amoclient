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
            $values[$key] = $isEnumId ? ['enum_id' => is_numeric($value) ? (int) $value : 0] : ['value' => $this->setValue($id, $value)];
        }

        if (isset($this->enums[$id])) {
            if (empty($values)) {
                $this->custom_fields_values[] = ['field_id' => $id, 'values' => null];
            } else {
                $enumsJson = $this->enums[$id];
                $decodedEnums = is_string($enumsJson) ? json_decode($enumsJson, true) : null;
                $enums = is_array($decodedEnums) ? Arr::pluck($decodedEnums, 'value', 'id') : [];
                $enumKey = is_int($value) || is_string($value) ? $value : null;
                if (in_array($value, $enums) || ($enumKey !== null && array_key_exists($enumKey, $enums)) || in_array('WORK', $enums)) {
                    $this->custom_fields_values[] = ['field_id' => $id, 'values' => $values];
                }
            }
        } elseif (array_key_exists($id, $this->cf)) {
            $this->custom_fields_values[] = ['field_id' => $id, 'values' => $values];
        }

        return $this;
    }

    // TODO: refactor ИЗБАВИТЬСЯ ОТ MIXED
    private function setValue(int $id, mixed $value): mixed
    {
        if ($type = $this->cf[$id] ?? null) {
            switch ($type) {
                case 'textarea':
                case 'multitext':
                    return self::toScalarString($value);
                case 'url':
                    return Str::limit(self::toScalarString($value), 2000, '');
                case 'text':
                    return Str::limit(self::toScalarString($value), 255, '');
                case 'numeric':
                    return is_numeric($value) ? (float) $value : 0.0;
                case 'date_time':
                case 'date':
                    try {
                        $value = strip_tags(self::toScalarString($value));

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
                        $value = strip_tags(self::toScalarString($value));
                        $parsed = ! is_numeric($value) ?
                            Carbon::parseFromLocale(str_replace('&nbsp;', ' ', $value), 'ru') :
                            Carbon::createFromTimestamp((int) $value);

                        return $parsed->format('Y-m-d\\TH:i:sP');
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

    /* amo-значения кастомных полей приходят как mixed (форма/API), а не
     * приводятся кастом напрямую — level: max запрещает cast mixed → string
     * без гарда. */
    private static function toScalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCF(int $id): array
    {
        return empty($this->custom_fields_values) ? [] :
            Arr::where($this->custom_fields_values, fn ($i) => isset($i['field_id']) && $i['field_id'] == $id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCFByCode(string $code): array
    {
        return empty($this->custom_fields_values) ? [] :
            Arr::where($this->custom_fields_values, fn ($i) => isset($i['field_code']) && $i['field_code'] == $code);
    }

    public function getCFV(int $id): mixed
    {
        return self::firstCFValue($this->getCF($id), 'value');
    }

    public function getCFVByCode(string $code): mixed
    {
        return self::firstCFValue($this->getCFByCode(Str::upper($code)), 'value');
    }

    public function getCFE(int $id): ?int
    {
        $enumId = self::firstCFValue($this->getCF($id), 'enum_id');

        return is_numeric($enumId) ? (int) $enumId : null;
    }

    /**
     * values[0][$key] первого элемента $cf — сама структура custom_fields_values
     * динамическая (форма amo API), поэтому гардим is_array() на каждом
     * уровне вместо offset-доступа на mixed.
     *
     * @param  array<int, array<string, mixed>>  $cf
     */
    private static function firstCFValue(array $cf, string $key): mixed
    {
        $first = Arr::first($cf);
        $values = is_array($first) ? ($first['values'] ?? null) : null;
        $firstValue = is_array($values) ? ($values[0] ?? null) : null;

        return is_array($firstValue) ? ($firstValue[$key] ?? null) : null;
    }

    /**
     * @return array<string>
     */
    public function getCFCLN(int $id): array
    {
        $names = [];
        $f = Arr::first($this->getCF($id));
        $values = is_array($f) ? ($f['values'] ?? null) : null;

        if (is_array($values)) {
            foreach ($values as $value) {
                if (! is_array($value)) {
                    continue;
                }

                $catalogId = self::toScalarString($value['catalog_id'] ?? null);
                $catalogElementId = self::toScalarString($value['catalog_element_id'] ?? null);
                $el = $this->http->get("catalogs/{$catalogId}/elements/{$catalogElementId}")->json();
                $name = is_array($el) ? ($el['name'] ?? null) : null;

                if (is_string($name)) {
                    $names[] = $name;
                }
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
        $first = Arr::first($f);
        $values = is_array($first) ? ($first['values'] ?? []) : [];
        $values = is_array($values) ? $values : [];

        if (! count($f)) {
            return [];
        }

        $result = [];
        foreach ($values as $item) {
            $value = is_array($item) ? ($item['value'] ?? null) : null;
            $result[] = is_scalar($value) ? (string) $value : '';
        }

        return $result;
    }
}
