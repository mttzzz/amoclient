<?php

namespace mttzzz\AmoClient\Tests\Support;

use mttzzz\AmoClient\Models\AbstractModel;
use mttzzz\AmoClient\Models\Company;
use mttzzz\AmoClient\Models\Contact;
use mttzzz\AmoClient\Models\Lead;
use mttzzz\AmoClient\Models\Note;
use mttzzz\AmoClient\Models\Task;
use mttzzz\AmoClient\Models\Unsorted;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Пришпиливает карту «модель → поля сортировки» к эмпирическому замеру (§9.4).
 *
 * ЗАЧЕМ ЭТО ОТДЕЛЬНЫЙ ТЕСТ. Амо не валидирует `order`: неподдерживаемое поле
 * получает HTTP 200 и выдачу в порядке по умолчанию. Значит «метод вызывается
 * без ошибки» не означает «сортировка работает», и никакой обычный тест этого
 * не поймает — он увидит успешный ответ. Единственный способ узнать правду —
 * замер: запросить страницу дважды, `asc` и `desc`; совпали, значит поле
 * игнорируется.
 *
 * Матрица, снятая этим способом:
 *
 * | Роут | id | updated_at | created_at | complete_till | name |
 * |---|---|---|---|---|---|
 * | leads | ✅ | ✅ | ✅ | ✕ | ✕ |
 * | contacts | ✅ | ✅ | ✕ | ✕ | ✅ |
 * | companies | ✅ | ✅ | ✕ | ✕ | ✅ |
 * | tasks | ✅ | ✕ | ✅ | ✅ | ✕ |
 * | leads/notes | ✅ | ✅ | ✕ | ✕ | ✕ |
 *
 * Замер нашёл в библиотеке дефект обоих знаков сразу: общий трейт «три поля
 * разом» поставлял контактам и компаниям `orderByCreatedAt…`, который эти роуты
 * игнорируют, — два метода молча ничего не делали; и он же не поставлял им
 * `orderByName…`, который как раз работает.
 *
 * ПОЭТОМУ ТЕСТ ПРОВЕРЯЕТ И ОТСУТСТВИЕ МЕТОДОВ, а не только наличие. Наличие
 * ловит потерю возможности, отсутствие — возврат тихого no-op. Попытка
 * «добавить недостающий метод для симметрии» (самый вероятный будущий мотив:
 * у сделок есть created_at, почему у контактов нет) упрётся здесь в красный
 * тест и потребует нового замера, а не рассуждения по аналогии. Аналогия между
 * соседними роутами амо за эту смену подвела трижды.
 *
 * Тест оффлайновый: сверяет объявления через `method_exists()`, в амо не ходит
 * и боевой аккаунт не трогает. Фактический порядок выдачи проверяет свип, на
 * данных.
 *
 * `Models\Unsorted` стоит в карте с ПУСТЫМ набором: замер (§9.7) показал, что
 * роут игнорирует сортировку целиком — `asc`, `desc` и запрос вообще без
 * параметра дают одну и ту же выдачу. Модель поставляла два метода, из которых
 * первый был тихим no-op, а второй «работал» случайно, совпадая с порядком по
 * умолчанию (от новых к старым). Пустая строка карты запрещает вернуть их.
 *
 * У `Unsorted` методы звались `orderCreatedAt…`, без `By`, поэтому карта их
 * прямо не назвала бы. Проверка построена на трейтовых именах намеренно:
 * возвращать сортировку сюда будут именно ими, общей формой.
 */
class OrderCapabilityMapTest extends TestCase
{
    /**
     * Поле → префикс методов сортировки.
     *
     * `complete_till` исторически зовётся `orderByComplete…`, без `Till`: имя
     * отгружено и стоит в вызовах, переименование ради симметрии с именем поля
     * ломало бы потребителей ради косметики.
     *
     * @var array<string, string>
     */
    private const METHOD_PREFIX = [
        'id' => 'orderById',
        'updated_at' => 'orderByUpdatedAt',
        'created_at' => 'orderByCreatedAt',
        'name' => 'orderByName',
        'complete_till' => 'orderByComplete',
    ];

    /**
     * @return iterable<string, array{class-string<AbstractModel>, list<string>}>
     */
    public static function measuredMatrix(): iterable
    {
        yield 'leads' => [Lead::class, ['id', 'updated_at', 'created_at']];
        yield 'contacts' => [Contact::class, ['id', 'updated_at', 'name']];
        yield 'companies' => [Company::class, ['id', 'updated_at', 'name']];
        yield 'tasks' => [Task::class, ['id', 'created_at', 'complete_till']];
        yield 'leads/notes' => [Note::class, ['id', 'updated_at']];
        yield 'leads/unsorted' => [Unsorted::class, []];
    }

    /**
     * @param  class-string<AbstractModel>  $model
     * @param  list<string>  $supported
     */
    #[DataProvider('measuredMatrix')]
    public function test_model_exposes_exactly_the_measured_order_methods(string $model, array $supported): void
    {
        foreach ($supported as $field) {
            /* Опечатка в имени поля иначе прошла бы молча: строка просто
             * перестала бы что-либо требовать, и тест остался бы зелёным,
             * ничего не проверяя. */
            $this->assertArrayHasKey(
                $field,
                self::METHOD_PREFIX,
                "Поле «{$field}» названо в карте, но не описано в METHOD_PREFIX — карта проверяет не то, что думает."
            );
        }

        foreach (self::METHOD_PREFIX as $field => $prefix) {
            $measuredAsWorking = in_array($field, $supported, true);

            foreach (['Asc', 'Desc'] as $direction) {
                $method = $prefix.$direction;

                if ($measuredAsWorking) {
                    $this->assertTrue(
                        method_exists($model, $method),
                        "{$model}::{$method}() отсутствует, хотя замер (§9.4) подтвердил, что поле «{$field}» на этом роуте работает."
                    );

                    continue;
                }

                $this->assertFalse(
                    method_exists($model, $method),
                    "{$model}::{$method}() существует, хотя замер (§9.4) показал, что роут ИГНОРИРУЕТ поле «{$field}»: "
                    .'амо отвечает 200 и отдаёт порядок по умолчанию, то есть метод молча ничего не делает. '
                    .'Считаете, что поддержка появилась — снимите замер (страница дважды, asc и desc, сравнить), а не рассуждение по аналогии.'
                );
            }
        }
    }
}
