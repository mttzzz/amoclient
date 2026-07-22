<?php

namespace mttzzz\AmoClient\Traits\Order;

/**
 * Сортировка по `complete_till` — сроку задачи. Замером (§9.4) подтверждена
 * только для tasks; у остальных роутов такого поля нет.
 *
 * Имена методов сохранены как `orderByComplete…`, без `Till`: они отгружены в
 * этой форме и стоят в вызовах. Переименовывать ради симметрии с именем трейта
 * значит ломать потребителей ради косметики — расхождение оставлено намеренно.
 *
 * К свежести отношения не имеет: срок задаётся вручную и в прошлое тоже.
 * Для «самые свежие первыми» у задач берите `ById`.
 *
 * Почему поля разнесены по отдельным трейтам — см. `AbstractModel::orderBy()`.
 */
trait ByCompleteTill
{
    public function orderByCompleteAsc(): self
    {
        return $this->orderBy('complete_till', 'asc');
    }

    public function orderByCompleteDesc(): self
    {
        return $this->orderBy('complete_till', 'desc');
    }
}
