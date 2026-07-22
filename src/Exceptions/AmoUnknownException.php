<?php

namespace mttzzz\AmoClient\Exceptions;

use Exception;

/**
 * Ответ амо не классифицирован: HTTP-успех, но тело, которого операция не знает.
 *
 * Семантика — «громкая тревога»: не ретраится, не глушится, всегда долетает до
 * Sentry. Нераспознанный ответ это либо дрейф контракта амо, либо наша неверная
 * модель этого контракта; и то и другое обязано быть громким, а не превращаться
 * в пустой массив или в тихое «ну наверное удалилось».
 *
 * Имя взято из таксономии SP1 (docs/superpowers/specs/2026-07-22-amoclient-
 * error-taxonomy-and-resilience-design.md, §2.2–2.3) намеренно: смысл там тот
 * же самый, и заводить второе имя под него значит обречь SP1 на переименование
 * публичного типа. Сегодня наследуется от AmoCustomException, потому что общего
 * AmoException ещё нет; SP1 переродит его от AmoException — смена родителя
 * внутри иерархии дешевле смены имени в API.
 *
 * Конструктор родителя принимает транспортное исключение, которого у нас нет
 * (HTTP был успешным), поэтому вызывается конструктор Exception напрямую —
 * это осознанный проброс через голову AmoCustomException, а не забытый parent.
 */
class AmoUnknownException extends AmoCustomException
{
    /**
     * Сырой ответ амо целиком — чтобы разбор дрейфа не требовал повторного
     * похода в боевой аккаунт.
     *
     * @var array<mixed>
     */
    public readonly array $response;

    /** Операция в терминах библиотеки, например `delete tasks`. */
    public readonly string $operation;

    /**
     * @param  array<mixed>  $response
     */
    public function __construct(string $operation, string $reason, array $response)
    {
        $this->operation = $operation;
        $this->response = $response;

        $encoded = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Exception::__construct(sprintf(
            '%s: %s. Ответ амо: %s',
            $operation,
            $reason,
            $encoded === false ? '<ответ не сериализуется в json>' : $encoded
        ));
    }
}
