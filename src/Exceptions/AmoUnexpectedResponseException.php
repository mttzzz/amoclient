<?php

namespace mttzzz\AmoClient\Exceptions;

use Exception;

/**
 * Амо ответил HTTP-успехом, но телом, которое операция прочитать не умеет.
 *
 * Отдельный тип нужен ровно затем, чтобы такой ответ нельзя было спутать с
 * транспортной ошибкой (AmoCustomException оборачивает RequestException и
 * ConnectionException) и чтобы потребитель не глушил его заодно с ними.
 * Нераспознанный ответ — это либо дрейф контракта амо, либо наша неверная
 * модель этого контракта; и то и другое обязано быть громким, а не
 * превращаться в пустой массив или в тихое «ну наверное удалилось».
 */
class AmoUnexpectedResponseException extends Exception
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

        parent::__construct(sprintf(
            '%s: %s. Ответ амо: %s',
            $operation,
            $reason,
            $encoded === false ? '<ответ не сериализуется в json>' : $encoded
        ));
    }
}
