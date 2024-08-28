<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class ShortLink extends AbstractEntity
{
    public string $url;

    public function __construct(PendingRequest $http)
    {
        parent::__construct([], $http);
        $this->entity = 'short_links';
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AmoCustomException
     */
    public function create(): array
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
            // @codeCoverageIgnoreStart
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
            // @codeCoverageIgnoreEnd
        }
    }

    public function createGetUrl(): string
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json()['_embedded']['short_links'][0]['url'];
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setContactId(int $contactId): self
    {
        $this->metadata = ['entity_type' => 'contacts', 'entity_id' => $contactId];

        return $this;
    }
}
