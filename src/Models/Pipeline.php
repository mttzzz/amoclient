<?php

namespace mttzzz\AmoClient\Models;

use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Pipeline extends AbstractModel
{
    use Traits\CrudTrait;

    protected $entity = 'leads/pipelines';

    public function __construct($http)
    {
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Pipeline(['id' => $id], $this->http);
    }

    public function find($id)
    {
        return new Entities\Pipeline($this->findEntity($id), $this->http);
    }
}
