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
     * @return array<mixed>
     *
     * @throws AmoCustomException
     */
    public function create(): array
    {
        try {
            $result = $this->http->post($this->entity, [$this->toArray()])->throw()->json();

            return is_array($result) ? $result : [];
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * Convert the object to an array.
     *
     * $http/$entity — служебные поля, не часть API-payload; строим массив
     * вручную через get_object_vars() вместо unset()+(array)-каста, чтобы не
     * трогать typed-свойства (level: max ругается на unset возможно
     * хукнутого свойства, PHP 8.4 property hooks).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $item = [];

        foreach (get_object_vars($this) as $key => $value) {
            if (in_array($key, ['http', 'entity'], true)) {
                continue;
            }

            $item[(string) $key] = $value;
        }

        return $item;
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
