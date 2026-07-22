<?php

namespace mttzzz\AmoClient\Traits\Order;

/**
 * Сортировка по `name`. Замером (§9.4) подтверждена для contacts и companies —
 * и это второй результат матрицы, зеркальный к `created_at`: поле РАБОТАЕТ, а
 * библиотека его не поставляла вовсе.
 *
 * У leads, tasks и leads/notes игнорируется.
 *
 * Почему поля разнесены по отдельным трейтам — см. `AbstractModel::orderBy()`.
 */
trait ByName
{
    public function orderByNameAsc(): self
    {
        return $this->orderBy('name', 'asc');
    }

    public function orderByNameDesc(): self
    {
        return $this->orderBy('name', 'desc');
    }
}
