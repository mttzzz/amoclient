<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;

class Note extends AbstractEntity
{
    protected $entity;
    protected $parentId;
    public $id, $entity_id, $note_type = 'common', $params = [];

    public function __construct($data, PendingRequest $http, $parentEntity, $parentId = null)
    {
        $this->entity = $parentEntity . '/notes';
        $this->entity_id = $parentId;
        parent::__construct($data, $http);
    }

    public function createCommon($text)
    {
        $this->params = compact('text');
        return $this->create();
    }

    public function createInvoice($text, $service, $icon_url)
    {
        $this->note_type = 'invoice_paid';
        $this->params = compact('text', 'service', 'icon_url');
        return $this->create();
    }

    public function setParams($params)
    {
        $this->params = $params;
    }
}
