<?php

namespace mttzzz\AmoClient\Entities;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Traits;

class OctanePipeline
{
    public int $id;

    public string $name;

    public int $sort;

    public int $is_main;

    public int $is_unsorted_on;

    public int $account_id;

    public int $created_by;

    public int $updated_by;

    public string $created_at;

    public string $updated_at;
}

class Lead extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\CustomFieldTrait, Traits\TagTrait;

    const UTM_FIELDS = ['fbclid', 'yclid', 'referrer', 'gclid', 'gclientid', 'from', 'openstat_source', 'openstat_ad', 'openstat_campaign',
        'openstat_service', 'utm_source', 'roistat', '_ym_counter', '_ym_uid', 'utm_referrer', 'utm_content', 'utm_term',
        'utm_campaign', 'utm_medium',
    ];

    public ?string $name;

    public Models\Note $notes;

    public Task $tasks;

    public Models\Link $links;

    public int $price;

    public int $status_id;

    public int $pipeline_id;

    public int $group_id;

    public int $created_by;

    public bool $is_price_computed;

    /**
     * @var array<mixed>
     */
    public array $custom_fields_values = [];

    /**
     * @var array<mixed>
     */
    public array $_embedded = [];

    /**
     * @param  array<mixed>  $data
     * @param  array<mixed>  $cf
     * @param  array<mixed>  $enums
     */
    public function __construct($data, PendingRequest $http, array $cf, array $enums)
    {
        parent::__construct($data, $http);
        $this->entity = 'leads';
        $this->cf = $cf;
        $this->enums = $enums;
        $this->tasks = new Task(['responsible_user_id' => $this->responsible_user_id], $http, $this->entity, $this->id);
        $this->links = new Models\Link($http, "{$this->entity}/{$this->id}");
        $this->notes = new Models\Note($http, "{$this->entity}/{$this->id}", $this->id);
    }

    /**
     * @return array<mixed>
     */
    public function complex(): array
    {
        try {
            return $this->http->post($this->entity.'/complex', [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function setContact(Contact $contact): void
    {
        $this->_embedded['contacts'][] = $contact->toArray();
    }

    public function setCompany(Company $company): void
    {
        $this->_embedded['companies'][] = $company->toArray();
    }

    public function getMainContactId(): ?int
    {
        if (! isset($this->_embedded['contacts'])) {
            throw new Exception('add withContacts() before call this function');
        }
        foreach ($this->_embedded['contacts'] as $contact) {
            if ($contact['is_main']) {
                return $contact['id'];
            }
        }

        return null;
    }

    public function getCompanyId(): ?int
    {
        return $this->_embedded['companies'][0]['id'] ?? null;
    }

    public function getCompanyName(): string
    {
        $companyId = $this->getCompanyId();

        return $companyId ? $this->http->get("companies/$companyId")->json('name') : '';
    }

    public function getPipelineName(): string
    {
        /** @var OctanePipeline|null $pipeline */
        $pipeline = DB::connection('octane')->table('account_pipelines')
            ->where('account_id', $this->account_id)
            ->where('id', $this->pipeline_id)
            ->first();

        return $pipeline ? $pipeline->name : '';
    }

    /**
     * @return array<int>
     */
    public function getCatalogElementIds(int $catalogId): array
    {
        $catalogElementIds = $this->_embedded['catalog_elements'] ?? [];
        foreach ($catalogElementIds as $key => &$catalogElementId) {
            if ((int) $catalogElementId['metadata']['catalog_id'] === (int) $catalogId) {
                $catalogElementId = $catalogElementId['id'];
            } else {
                unset($catalogElementIds[$key]);
            }
        }

        return $catalogElementIds;
    }

    public function getCatalogQuantity(int $catalogId): int|float
    {
        $quantity = 0;
        $catalogElementIds = $this->_embedded['catalog_elements'] ?? [];
        foreach ($catalogElementIds as $key => $catalogElementId) {
            if ((int) $catalogElementId['metadata']['catalog_id'] === (int) $catalogId) {
                $quantity += $catalogElementId['metadata']['quantity'];
            }
        }

        return $quantity;
    }

    public function getCatalogElementQuantity(int $catalogId, int $elementId): float|int
    {
        $catalogElementIds = $this->_embedded['catalog_elements'] ?? [];
        foreach ($catalogElementIds as $key => $catalogElementId) {
            if ((int) $catalogElementId['metadata']['catalog_id'] === (int) $catalogId && (int) $elementId == (int) $catalogElementId['id']) {
                return $catalogElementId['metadata']['quantity'];
            }
        }

        return 0;
    }

    /**
     * @return array<int>
     */
    public function getContactsIds(): array
    {
        if (! isset($this->_embedded['contacts'])) {
            throw new Exception('add withContacts() before call this function');
        }
        $ids = [];
        foreach ($this->_embedded['contacts'] as $contact) {
            $ids[] = $contact['id'];
        }

        return $ids;
    }
}
