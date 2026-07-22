<?php

namespace mttzzz\AmoClient;

use Illuminate\Support\Facades\DB;
use stdClass;

/*
 * Ленивый загрузчик account_custom_fields для AmoClientOctane.
 *
 * SELECT id, type, enums FROM account_custom_fields WHERE account_id = ?
 * выполняется ТОЛЬКО при первом обращении к cf() или enums(). Если caller
 * не вызывает Lead/Customer/Contact/Company::entity/one/find — запрос не
 * пойдёт никогда. По pg_stat 107.9s / 66k calls / 11 дней.
 */
final class LazyCustomFields
{
    /** @var array<int|string, string>|null */
    private ?array $cf = null;

    /** @var array<int|string, string|null>|null */
    private ?array $enums = null;

    public function __construct(private readonly int $accountId) {}

    /** @return array<int|string, string> */
    public function cf(): array
    {
        $this->load();

        return $this->cf ?? [];
    }

    /** @return array<int|string, string|null> */
    public function enums(): array
    {
        $this->load();

        return $this->enums ?? [];
    }

    private function load(): void
    {
        if ($this->cf !== null) {
            return;
        }

        $rows = DB::connection('octane')->select(
            'SELECT id, type, enums FROM account_custom_fields WHERE account_id = ?',
            [$this->accountId]
        );

        $cf = [];
        $enums = [];
        foreach ($rows as $row) {
            /* raw SQL-строка — PDO отдаёт stdClass, но select() типизирован
             * как array (без generics), гардим перед доступом к свойствам. */
            if (! $row instanceof stdClass) {
                continue;
            }

            $id = $row->id ?? null;
            if (! is_int($id) && ! is_string($id)) {
                continue;
            }

            $type = $row->type ?? null;
            $cf[$id] = is_string($type) ? $type : '';

            $rowEnums = $row->enums ?? null;
            if (is_array($rowEnums)) {
                $encoded = json_encode($rowEnums);
                $enums[$id] = $encoded === false ? null : $encoded;
            } else {
                $enums[$id] = is_string($rowEnums) ? $rowEnums : null;
            }
        }

        $this->cf = $cf;
        $this->enums = $enums;
    }
}
