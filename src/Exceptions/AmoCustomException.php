<?php

namespace mttzzz\AmoClient\Exceptions;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class AmoCustomException extends Exception
{
    public function __construct(ConnectionException|RequestException $e)
    {
        if ($e->getCode() == 402) {
            parent::__construct('Амо не оплачен', 402);
        } elseif ($e instanceof RequestException) {
            $responseBody = $e->response->body();
            $decodedBody = json_decode($responseBody);
            $message = json_encode($decodedBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            if ($message === false) {
                $message = 'Invalid JSON response (RequestException)';
            }
            parent::__construct($message, $e->getCode());
        } else {
            parent::__construct('Unknown error (ConnectionException)', $e->getCode());
        }
    }
}
