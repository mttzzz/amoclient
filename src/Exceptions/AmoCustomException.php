<?php

namespace mttzzz\AmoClient\Exceptions;

use Exception;
use Illuminate\Http\Client\RequestException;

class AmoCustomException extends Exception
{
    public function __construct(RequestException $e)
    {
        $e->getCode() == 402 ?
            parent::__construct('Амо не оплачен', 402) :
            parent::__construct(json_encode(json_decode($e->response->body()), 64 | 128 | 256), $e->getCode());
    }
}
