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
    public array $_embedded = [], $metadata = [];

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
        unset($this->entity);
        return (array)$this;
    }

    public function addLead(Lead $lead)
    {
        $this->_embedded['leads'][] = $lead->toArray();
    }

    public function addContact(Contact $contact)
    {
        $this->_embedded['contacts'][] = $contact->toArray();
    }

    public function addCompany(Company $company)
    {
        $this->_embedded['companies'][] = $company->toArray();
    }
}
