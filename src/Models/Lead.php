<?php

namespace mttzzz\AmoClient\Models;

use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Lead extends AbstractModel
{
    use Traits\QueryTrait;

    protected $entity = 'leads';
    protected $class = 'Lead';

    public function __construct($http)
    {
        parent::__construct($http);
    }

    public function find($id): Entities\Lead
    {
        return parent::find($id);
    }

    public function entity()
    {
        return new Entities\Lead([], $this->http);
    }

    public function note()
    {
        return new Note($this->http, $this->entity);
    }
}
