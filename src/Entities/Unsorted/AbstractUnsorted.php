<?php

namespace mttzzz\AmoClient\Entities\Unsorted;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities\Company;
use mttzzz\AmoClient\Entities\Contact;
use mttzzz\AmoClient\Entities\Lead;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

abstract class AbstractUnsorted
{
    public string $source_uid, $source_name;
    public int $pipeline_id, $created_at;
    protected $http, $entity = 'leads/unsorted';
    protected array $_embedded = [], $metadata = [];

    public function __construct(PendingRequest $http = null)
    {
        $this->http = $http;
    }

    public function create()
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function toArray()
    {
        unset($this->http);
        return (array)$this;
    }

    public function addLead(Lead $lead)
    {
        $_embedded['leads'][] = $lead->toArray();
    }

    public function addContact(Contact $contact)
    {
        $_embedded['leads'][] = $contact->toArray();
    }

    public function addCompany(Company $company)
    {
        $_embedded['companies'][] = $company->toArray();
    }
}
