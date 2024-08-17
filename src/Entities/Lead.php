<?php

namespace mttzzz\AmoClient\Entities;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Traits;

class Lead extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\CustomFieldTrait, Traits\TagTrait;

    const UTM_FIELDS = ['fbclid', 'yclid', 'referrer', 'gclid', 'gclientid', 'from', 'openstat_source', 'openstat_ad', 'openstat_campaign',
        'openstat_service', 'utm_source', 'roistat', '_ym_counter', '_ym_uid', 'utm_referrer', 'utm_content', 'utm_term',
        'utm_campaign', 'utm_medium',
    ];

    protected $entity = 'leads';

    public $name;

    public $notes;

    public $tasks;

    public $links;

    public $id;

    public $price;

    public $status_id;

    public $responsible_user_id;

    public $pipeline_id;

    public $custom_fields_values = [];

    public $_embedded = [];

    public function __construct($data, PendingRequest $http, $cf, $enums)
    {
        $this->cf = $cf;
        $this->enums = $enums;
        parent::__construct($data, $http);
        $this->notes = new Note([], $http, $this->entity, $this->id);
        $this->tasks = new Task(['responsible_user_id' => $this->responsible_user_id], $http, $this->entity, $this->id);
        $this->links = new Models\Link($http, "{$this->entity}/{$this->id}");
        $this->notes = new Models\Note($http, "{$this->entity}/{$this->id}", $this->id);
    }

    public function complex()
    {
        try {
            return $this->http->post($this->entity.'/complex', [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function setContact(Contact $contact)
    {
        $this->_embedded['contacts'][] = $contact->toArray();
    }

    public function setCompany(Company $company)
    {
        $this->_embedded['companies'][] = $company->toArray();
    }

    public function getMainContactId()
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

    public function getCompanyId()
    {
        return $this->_embedded['companies'][0]['id'] ?? null;
    }

    public function getCompanyName(): string
    {
        $companyId = $this->getCompanyId();

        return $companyId ? $this->http->get("companies/$companyId")->json('name') : '';
    }

    public function getPipelineName()
    {
        $pipeline = DB::connection('octane')->table('account_pipelines')
            ->where('account_id', $this->account_id)
            ->where('id', $this->pipeline_id)
            ->first();

        return $pipeline?->name;
    }

    public function getCatalogElementIds($catalogId)
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

    public function getCatalogQuantity($catalogId)
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

    public function getCatalogElementQuantity($catalogId, $elementId)
    {
        $catalogElementIds = $this->_embedded['catalog_elements'] ?? [];
        foreach ($catalogElementIds as $key => $catalogElementId) {
            if ((int) $catalogElementId['metadata']['catalog_id'] === (int) $catalogId && (int) $elementId == (int) $catalogElementId['id']) {
                return $catalogElementId['metadata']['quantity'];
            }
        }

        return 0;
    }

    public function getContactsIds()
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
