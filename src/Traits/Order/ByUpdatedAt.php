<?php

namespace mttzzz\AmoClient\Traits\Order;

/**
 * Сортировка по `updated_at`. Замером (§9.4) подтверждена для leads, contacts,
 * companies и leads/notes.
 *
 * У ЗАДАЧ ЭТО ПОЛЕ ИГНОРИРУЕТСЯ — `asc` и `desc` дают одну и ту же страницу при
 * HTTP 200, и официальный справочник его в списке задач тоже не называет.
 * Подключать трейт к `Models\Task` нельзя: получится метод, который молча
 * ничего не делает.
 *
 * Почему поля разнесены по отдельным трейтам — см. `AbstractModel::orderBy()`.
 */
trait ByUpdatedAt
{
    public function orderByUpdatedAtAsc(): self
    {
        return $this->orderBy('updated_at', 'asc');
    }

    public function orderByUpdatedAtDesc(): self
    {
        return $this->orderBy('updated_at', 'desc');
    }
}
