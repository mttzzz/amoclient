<?php


namespace mttzzz\AmoClient\Models;

use mttzzz\AmoClient\Entities;

class Catalog extends AbstractModel
{
    protected $entity = 'catalogs';
    protected $class = 'Catalog';
    protected $catalogId;

    public function __construct($http)
    {
        parent::__construct($http);
    }

    public function find($id): Entities\Catalog
    {
        return parent::find($id);
    }

    public function entity($id)
    {
        return new Entities\Catalog(['id' => $id], $this->http);
    }
}
