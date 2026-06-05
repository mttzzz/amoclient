<?php

namespace mttzzz\AmoClient\Exceptions;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class AmoCustomException extends Exception
{
    /*
     * Оригинал всегда сохраняется как previous — потребители (Sentry,
     * retry-классификаторы) различают transient ConnectionException и
     * бизнес-ошибки через getPrevious(), а не по str_contains на message.
     */
    public function __construct(ConnectionException|RequestException $e)
    {
        if ($e->getCode() == 402) {
            parent::__construct('Амо не оплачен', 402, $e);
        } elseif ($e instanceof RequestException) {
            $responseBody = $e->response->body();
            $decodedBody = json_decode($responseBody);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $message = $e->getMessage();
            } else {
                $message = json_encode($decodedBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            // @phpstan-ignore argument.type
            parent::__construct($message, $e->getCode(), $e);
        } else {
            parent::__construct('Unknown error (ConnectionException)', $e->getCode(), $e);
        }
    }
}
