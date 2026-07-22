<?php

namespace mttzzz\AmoClient\Entities;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use mttzzz\AmoClient\Deleter;
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

    public int|float|null $sale = null;

    public int $status_id;

    public int $pipeline_id;

    public int $group_id;

    public int $created_by;

    public bool $is_price_computed;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $custom_fields_values = [];

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    public array $_embedded = [];

    /**
     * @param  array<string, mixed>  $data
     * @param  array<mixed>  $cf
     * @param  array<mixed>  $enums
     */
    public function __construct(array $data, PendingRequest $http, array $cf, array $enums, Deleter $deleter)
    {
        parent::__construct($data, $http);
        $this->entity = 'leads';
        $this->cf = $cf;
        $this->enums = $enums;
        $this->tasks = new Task(['responsible_user_id' => $this->responsible_user_id], $http, $this->entity, $this->id);
        $this->links = new Models\Link($http, "{$this->entity}/{$this->id}");
        $this->notes = new Models\Note($http, "{$this->entity}/{$this->id}", $this->id, $deleter);
    }

    /**
     * @return array<mixed>
     */
    public function complex(): array
    {
        try {
            $result = $this->http->post($this->entity.'/complex', [$this->toArray()])->throw()->json();

            return is_array($result) ? $result : [];
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
            if ($contact['is_main'] ?? false) {
                $id = $contact['id'] ?? null;

                return is_numeric($id) ? (int) $id : null;
            }
        }

        return null;
    }

    public function getCompanyId(): ?int
    {
        $companyId = $this->_embedded['companies'][0]['id'] ?? null;

        return is_numeric($companyId) ? (int) $companyId : null;
    }

    public function getCompanyName(): string
    {
        $companyId = $this->getCompanyId();

        if (! $companyId) {
            return '';
        }

        $name = $this->http->get("companies/$companyId")->json('name');

        return is_string($name) ? $name : '';
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
        $result = [];
        foreach ($catalogElementIds as $key => $catalogElementId) {
            if (self::catalogElementMetadataCatalogId($catalogElementId) === $catalogId) {
                $result[$key] = self::catalogElementId($catalogElementId);
            }
        }

        return $result;
    }

    public function getCatalogQuantity(int $catalogId): int|float
    {
        $quantity = 0;
        $catalogElementIds = $this->_embedded['catalog_elements'] ?? [];
        foreach ($catalogElementIds as $catalogElementId) {
            if (self::catalogElementMetadataCatalogId($catalogElementId) === $catalogId) {
                $quantity += self::catalogElementMetadataQuantity($catalogElementId);
            }
        }

        return $quantity;
    }

    public function getCatalogElementQuantity(int $catalogId, int $elementId): float|int
    {
        $catalogElementIds = $this->_embedded['catalog_elements'] ?? [];
        foreach ($catalogElementIds as $catalogElementId) {
            if (self::catalogElementMetadataCatalogId($catalogElementId) === $catalogId && $elementId === self::catalogElementId($catalogElementId)) {
                return self::catalogElementMetadataQuantity($catalogElementId);
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
            $id = $contact['id'] ?? null;
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * catalog_elements[i]['metadata']['catalog_id'] — вложенная структура
     * из ответа amo, значение по ключу типизировано как mixed; гардим
     * is_array/is_numeric вместо каста mixed напрямую.
     *
     * @param  array<string, mixed>  $catalogElement
     */
    private static function catalogElementMetadataCatalogId(array $catalogElement): int
    {
        $metadata = $catalogElement['metadata'] ?? null;
        $catalogId = is_array($metadata) ? ($metadata['catalog_id'] ?? null) : null;

        return is_numeric($catalogId) ? (int) $catalogId : 0;
    }

    /**
     * @param  array<string, mixed>  $catalogElement
     */
    private static function catalogElementMetadataQuantity(array $catalogElement): int|float
    {
        $metadata = $catalogElement['metadata'] ?? null;
        $quantity = is_array($metadata) ? ($metadata['quantity'] ?? null) : null;

        return is_numeric($quantity) ? $quantity + 0 : 0;
    }

    /**
     * @param  array<string, mixed>  $catalogElement
     */
    private static function catalogElementId(array $catalogElement): int
    {
        $id = $catalogElement['id'] ?? null;

        return is_numeric($id) ? (int) $id : 0;
    }
}
