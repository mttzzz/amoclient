<?php

namespace mttzzz\AmoClient\Entities\Unsorted;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use mttzzz\AmoClient\Entities\Company;
use mttzzz\AmoClient\Entities\Contact;
use mttzzz\AmoClient\Entities\Lead;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

abstract class AbstractUnsorted
{
    public string $source_uid;

    public string $source_name;

    public int $pipeline_id;

    public int $created_at;

    protected PendingRequest $http;

    protected string $entity = 'leads/unsorted';

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    public array $_embedded = [];

    /**
     * @var array<string, mixed>
     */
    public array $metadata = [];

    public function __construct(PendingRequest $http)
    {
        $this->http = $http;
    }

    /**
     * Create a new unsorted entity.
     *
     * @throws AmoCustomException
     */
    public function create(): Response
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * Convert the object to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        unset($this->http);
        unset($this->entity);

        return (array) $this;
    }

    public function addLead(Lead $lead): void
    {
        $this->_embedded['leads'][] = $lead->toArray();
    }

    public function addContact(Contact $contact): void
    {
        $this->_embedded['contacts'][] = $contact->toArray();
    }

    public function addCompany(Company $company): void
    {
        $this->_embedded['companies'][] = $company->toArray();
    }
}
