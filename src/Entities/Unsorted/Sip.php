<?php

namespace mttzzz\AmoClient\Entities\Unsorted;

use Illuminate\Http\Client\PendingRequest;

class Sip extends AbstractUnsorted
{
    public string $from, $phone, $link, $service_code;
    public int $called_at, $duration;
    public bool $is_call_event_needed;


    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity .= '/sip';
    }

    public function toArray()
    {
        $item = parent::toArray();

    }
}
