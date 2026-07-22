<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class Webhook extends AbstractModel
{
    protected Deleter $deleter;

    public function __construct(PendingRequest $http, Deleter $deleter)
    {
        parent::__construct($http);
        $this->entity = 'webhooks';
        $this->deleter = $deleter;
    }

    public function entity(?string $destination = null): Entities\Webhook
    {
        return new Entities\Webhook(['destination' => $destination], $this->http, $this->deleter);
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
            return new Entities\Webhook($webhook, $this->http, $this->deleter);
        }

        return new Entities\Webhook([], $this->http, $this->deleter);
    }

    /**
     * Отписка (настоящий hard delete, а не отключение).
     *
     * Единственный тип, который адресуется НЕ числовым id: роут принимает
     * destination. Для свипа по реестру это значит, что вебхук парой
     * (тип, int id) не сносится — нужен трек по destination.
     *
     * @param  string|list<string>  $destinations
     * @return bool false — подписки уже нет (404)
     *
     * @throws AmoCustomException
     */
    public function delete(string|array $destinations): bool
    {
        return $this->deleter->webhooks($destinations);
    }
}
