<?php

namespace mttzzz\AmoClient\Entities\Unsorted;

use Illuminate\Http\Client\PendingRequest;

class Sip extends AbstractUnsorted
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity .= '/sip';
    }

    public function addMetadata(
        string $uniq, int $duration,
        string $service_code, string $link, string $phone,
        int $called_at, string $from, bool $is_call_event_needed): self
    {
        $this->metadata = compact('uniq', 'duration', 'service_code', 'link', 'phone', 'called_at', 'from', 'is_call_event_needed');

        return $this;
    }
}
