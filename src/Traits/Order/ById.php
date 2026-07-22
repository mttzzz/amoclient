<?php

namespace mttzzz\AmoClient\Traits\Order;

/**
 * Сортировка по `id` — единственное поле, работающее во всех замеренных роутах
 * (§9.4): leads, contacts, companies, tasks, leads/notes.
 *
 * id монотонны, поэтому это же поле годится как замена `created_at` там, где
 * тот игнорируется: «самые свежие первыми» выражается через него без потерь.
 *
 * Почему поля разнесены по отдельным трейтам — см. `AbstractModel::orderBy()`.
 */
trait ById
{
    public function orderByIdAsc(): self
    {
        return $this->orderBy('id', 'asc');
    }

    public function orderByIdDesc(): self
    {
        return $this->orderBy('id', 'desc');
    }
}
