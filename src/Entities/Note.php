<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;

class Note extends AbstractEntity
{
    protected $entity = 'notes';
    protected $parentEntity;
    public $id, $entity_id, $note_type = 'common', $params = [];

    public function __construct($data, PendingRequest $http, $parentEntity)
    {
        $this->parentEntity = $parentEntity;
        parent::__construct($data, $http);
    }

    public function createCommon($entity_id, $text)
    {
        $this->entity_id = $entity_id;
        $this->params = compact('text');
        return $this->create();
    }

    public function createInvoice($entity_id, $text, $service, $icon_url)
    {
        $this->entity_id = $entity_id;
        $this->note_type = 'invoice_paid';
        $this->params = compact('text', 'service', 'icon_url');
        return $this->create();
    }

    protected function data()
    {
        return parent::data() + ['parentEntity' => $this->parentEntity];
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function toArray()
    {
        $item = parent::toArray();
        unset($item['parentEntity']);
        return $item;
    }
}
