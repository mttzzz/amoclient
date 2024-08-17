<?php


namespace mttzzz\AmoClient\Entities;


use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class ShortLink extends AbstractEntity
{
    protected string $entity = 'short_links', $url;
    protected $metadata;

    public function __construct(PendingRequest $http)
    {
        parent::__construct([], $http);
    }

    public function create(): array
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function url(string $url): ShortLink
    {
        $this->url = $url;
        return $this;
    }

    public function setContactId(int $contactId): ShortLink
    {
        $this->metadata = ['entity_type' => 'contacts', 'entity_id' => $contactId];
        return $this;
    }
}
