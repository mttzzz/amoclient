<?php

namespace mttzzz\AmoClient\Tests\Support;

/**
 * Адрес сущности, которую свипу РАЗРЕШЕНО снести.
 *
 * Смысл этого класса — в том, чего в нём нет: публичного конструктора.
 * Единственный способ получить экземпляр — фабрика fromMarked(), которая
 * отдаёт null, если в маркерном поле payload'а нет AmoTestSweeper::TEST_MARKER.
 * Стадия удаления в свипе принимает только SweepTarget, поэтому «снести
 * лишнее» — это не ошибка в условии фильтра, а необходимость сконструировать
 * объект, который сконструировать нельзя. Аккаунт 16117840 боевой; гард,
 * держащийся на дисциплине читателя, здесь стоил бы клиентских данных.
 */
final class SweepTarget
{
    /*
     * Поле-носитель маркера — по одному на тип. Гард смотрит только сюда, а не
     * по всему payload'у, и это несущее решение, а не экономия.
     *
     * Поиск маркера в json_encode($payload) расширил бы гард до «сущность
     * где-то упоминает маркер». Под это подпадает, например, реальная
     * клиентская сделка, к которой тест прицепил помеченное примечание: сама
     * сделка чужая, сносить её нельзя. Поле-носитель формулирует условие
     * точно — помечен именно этот объект, а не его окружение.
     */
    private const MARKER_FIELDS = [
        'leads' => 'name',
        'contacts' => 'name',
        'companies' => 'name',
        'catalogs' => 'name',
        'catalogElements' => 'name',
        'customers' => 'name',
        'sources' => 'name',
        'pipelines' => 'name',
        'tasks' => 'text',
        'notes' => 'params',
        'calls' => 'params',
        'webhooks' => 'destination',
    ];

    /**
     * @param  int|string  $handle  чем сущность адресуется в Deleter::byType():
     *                              id у всех типов, кроме вебхуков — те
     *                              адресуются destination'ом, id роут не принимает
     */
    private function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly int|string $handle,
    ) {}

    /**
     * Типы, для которых вообще определено маркерное поле.
     *
     * Нужен свипу для сверки таблиц: тип, у которого есть семантика удаления,
     * но нет поля-носителя (или наоборот), молча не ищется — и тогда «свип
     * отработал, ноль находок» неотличимо от «свип не искал». Ровно так из
     * покрытия однажды выпали pipelines.
     *
     * @return list<string>
     */
    public static function knownTypes(): array
    {
        return array_keys(self::MARKER_FIELDS);
    }

    /**
     * Помечен ли payload маркером тестов.
     *
     * Отдельно от fromMarked() затем, чтобы свип мог различить два разных
     * случая: «чужая сущность» (не помечена — пропускаем молча) и «наш хвост,
     * но адресовать нечем» (помечена, а fromMarked() вернул null — это надо
     * увидеть в отчёте, иначе хвост уйдёт незаметно).
     *
     * @param  array<string, mixed>  $payload  как вернул amo, без правок
     */
    public static function isMarked(string $type, array $payload): bool
    {
        $field = self::MARKER_FIELDS[$type] ?? null;

        if ($field === null) {
            /* Типа нет в таблице — пометить его нечем, значит и сносить нечего. */
            return false;
        }

        $value = $payload[$field] ?? null;

        /* params у примечаний и звонков — подмассив (text/source/link/uniq). */
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        /* Регистрозависимо и по точной подстроке: маркер машинный, «почти совпало» здесь не бывает. */
        return is_string($value) && str_contains($value, AmoTestSweeper::TEST_MARKER);
    }

    /**
     * @param  array<string, mixed>  $payload  как вернул amo, без правок
     * @return self|null null — либо не помечено, либо помечено, но без пригодного адреса
     */
    public static function fromMarked(string $type, array $payload): ?self
    {
        if (! self::isMarked($type, $payload)) {
            return null;
        }

        $id = is_numeric($payload['id'] ?? null) ? (int) $payload['id'] : 0;

        if ($type === 'webhooks') {
            /*
             * Вебхук — единственный тип, который v4 адресует не id, а адресом
             * подписки. id в списке тоже приходит, но роут удаления его не
             * принимает, поэтому пустой destination делает цель неадресуемой
             * даже при валидном id.
             */
            $destination = is_string($payload['destination'] ?? null) ? $payload['destination'] : '';

            return $destination === '' ? null : new self($type, $id, $destination);
        }

        return $id > 0 ? new self($type, $id, $id) : null;
    }
}
