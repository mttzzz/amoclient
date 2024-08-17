<?php

namespace mttzzz\AmoClient\Models;

use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Source extends AbstractModel
{
    protected $entity = 'sources';

    use Traits\CrudTrait;

    public function __construct($http)
    {
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Source(['id' => $id], $this->http);
    }

    public function find($id)
    {
        return new Entities\Source($this->findEntity($id), $this->http);
    }
}
