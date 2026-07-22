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

    public function find(string $destination): Entities\Webhook
    {
        $result = $this->http->get($this->entity, ['filter' => ['destination' => $destination]])->throw()->json();
        $data = is_array($result) ? $result : [];

        $embedded = $data['_embedded'] ?? null;
        $webhooks = is_array($embedded) ? ($embedded['webhooks'] ?? null) : null;
        $webhook = is_array($webhooks) ? ($webhooks[0] ?? null) : null;

        if (is_array($webhook)) {
            /* amo API отдаёт JSON-объект — ключи всегда строки, но json()
             * типизирован как mixed, is_array() даёт лишь array<mixed>. */
            /** @var array<string, mixed> $webhook */
            return new Entities\Webhook($webhook, $this->http);
        }

        return new Entities\Webhook([], $this->http);
    }
}
