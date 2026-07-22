<?php

namespace mttzzz\AmoClient\Traits;

/**
 * Направление сортировки — не параметр, а имя метода, и это защита, а не стиль.
 *
 * Амо не валидирует `order`: на `order[updated_at]=nonsense` он отвечает 200 и
 * молча отдаёт порядок по умолчанию. Значит опечатка или неподдерживаемое
 * значение не проявились бы ни ошибкой, ни предупреждением — только тихой
 * деградацией у того, кто на сортировку полагается (у свипа это промах мимо
 * собственного мусора). Перечисленные методы делают строку с направлением
 * невозможной по построению: взяться ей неоткуда.
 *
 * РАСХОЖДЕНИЕ, о котором стоит знать: здесь вызовы НАКАПЛИВАЮТСЯ, а у
 * `Models\Note` и `Models\Task` каждый вызов замещает предыдущий. Сортировка у
 * амо ровно одна, поэтому накопление отправляет два ключа и оставляет выбор за
 * амо — молча. Приводить к замещению здесь означает менять поведение уже
 * отгруженных методов у Lead/Contact/Company, поэтому оставлено как есть и
 * вынесено решением наружу, а не «починено» походя.
 */
trait OrderTrait
{
    public function orderByCreatedAtAsc(): self
    {
        $this->order['created_at'] = 'asc';

        return $this;
    }

    public function orderByCreatedAtDesc(): self
    {
        $this->order['created_at'] = 'desc';

        return $this;
    }

    public function orderByUpdatedAtAsc(): self
    {
        $this->order['updated_at'] = 'asc';

        return $this;
    }

    public function orderByUpdatedAtDesc(): self
    {
        $this->order['updated_at'] = 'desc';

        return $this;
    }

    public function orderByIdAsc(): self
    {
        $this->order['id'] = 'asc';

        return $this;
    }

    public function orderByIdDesc(): self
    {
        $this->order['id'] = 'desc';

        return $this;
    }
}
