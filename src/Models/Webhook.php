<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;

class Webhook extends AbstractModel
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'webhooks';
    }

    public function entity(?string $destination = null): Entities\Webhook
    {
        return new Entities\Webhook(['destination' => $destination], $this->http);
    }

    public function find(?string $destination = null): Entities\Webhook
    {
        $data = $this->http->get($this->entity, ['filter' => ['destination' => $destination]])->throw()->json() ?? [];
        if (isset($data['_embedded']['webhooks'][0])) {
            return new Entities\Webhook($data['_embedded']['webhooks'][0], $this->http);
        } else {
            return new Entities\Webhook([], $this->http);
        }

    }
}
