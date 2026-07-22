<?php

namespace mttzzz\AmoClient\Traits;

/**
 * Сортировка для сущностей, у которых она подтверждена: leads, contacts,
 * companies.
 *
 * ПОДДЕРЖКА СОРТИРОВКИ У АМО НЕ ЕДИНООБРАЗНА МЕЖДУ РОУТАМИ — это главное, что
 * нужно знать перед тем, как подключать трейт к новой модели. У примечаний
 * `order[updated_at]` работает (§8.7), у задач тот же параметр игнорируется
 * целиком: три противоположных значения дают побайтово одну и ту же выдачу
 * (§8.8). Оба роута отвечают на любую просьбу одинаково успешно, поэтому
 * **200 в ответ не означает, что параметр учтён**, и проверить это можно
 * только по данным.
 *
 * Отсюда правило: не подключать трейт целиком ради одного метода. Он приносит
 * три поля разом, и каждое неподтверждённое поле — не запас на будущее, а
 * метод, который молча ничего не делает. Лучше отсутствие возможности, чем её
 * видимость.
 *
 * Направление — имя метода, а не параметр; каждый вызов замещает предыдущий.
 * Причины обоих решений — в `AbstractModel::orderBy()`.
 */
trait OrderTrait
{
    public function orderByCreatedAtAsc(): self
    {
        return $this->orderBy('created_at', 'asc');
    }

    public function orderByCreatedAtDesc(): self
    {
        return $this->orderBy('created_at', 'desc');
    }

    public function orderByUpdatedAtAsc(): self
    {
        return $this->orderBy('updated_at', 'asc');
    }

    public function orderByUpdatedAtDesc(): self
    {
        return $this->orderBy('updated_at', 'desc');
    }

    public function orderByIdAsc(): self
    {
        return $this->orderBy('id', 'asc');
    }

    public function orderByIdDesc(): self
    {
        return $this->orderBy('id', 'desc');
    }
}
