<?php

namespace mttzzz\AmoClient\Traits\Order;

/**
 * Сортировка по `created_at`. Замером (§9.4) подтверждена только для leads и
 * tasks.
 *
 * У КОНТАКТОВ И КОМПАНИЙ ЭТО ПОЛЕ ИГНОРИРУЕТСЯ, хотя раньше поставлялось им
 * общим трейтом «три поля разом» — то есть библиотека отдавала два метода,
 * которые молча ничего не делали. Именно эта находка и разнесла сортировку по
 * отдельным трейтам. У примечаний поле тоже игнорируется.
 *
 * Для «самые свежие первыми» там, где `created_at` недоступен, берите `ById`:
 * id монотонны и дают тот же порядок.
 *
 * Почему поля разнесены по отдельным трейтам — см. `AbstractModel::orderBy()`.
 */
trait ByCreatedAt
{
    public function orderByCreatedAtAsc(): self
    {
        return $this->orderBy('created_at', 'asc');
    }

    public function orderByCreatedAtDesc(): self
    {
        return $this->orderBy('created_at', 'desc');
    }
}
