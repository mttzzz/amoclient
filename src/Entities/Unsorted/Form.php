<?php


namespace mttzzz\AmoClient\Entities\Unsorted;


use Illuminate\Http\Client\PendingRequest;

class Form extends AbstractUnsorted
{
    public string $form_id, $form_name, $form_page, $ip, $referer;
    public int $form_sent_at;

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity .= '/forms';
    }

    public function toArray()
    {

    }
}
