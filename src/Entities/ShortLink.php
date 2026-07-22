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
            $result = $this->http->post($this->entity, [$this->toArray()])->throw()->json();
            if (! is_array($result)) {
                return [];
            }

            /* json() декодит объект amo API всегда с string-ключами, но
             * стаб Response::json() типизирован как mixed — is_array()
             * даёт array<mixed>, а не array<string, mixed>. */
            /** @var array<string, mixed> $result */
            return $result;
            // @codeCoverageIgnoreStart
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
            // @codeCoverageIgnoreEnd
        }
    }

    public function createGetUrl(): string
    {
        $embedded = $this->create()['_embedded'] ?? null;
        $shortLinks = is_array($embedded) ? ($embedded['short_links'] ?? null) : null;
        $firstLink = is_array($shortLinks) ? ($shortLinks[0] ?? null) : null;
        $url = is_array($firstLink) ? ($firstLink['url'] ?? null) : null;

        return is_string($url) ? $url : '';
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
