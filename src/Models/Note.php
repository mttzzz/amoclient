<?php


namespace mttzzz\AmoClient\Models;

use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits\CrudTrait;
use mttzzz\AmoClient\Traits\QueryTrait;

class Note extends AbstractModel
{
    use CrudTrait, QueryTrait;

    protected $entity = 'notes';
    protected $parentEntity;
    protected $class = 'Note';

    public function __construct($http, $parentEntity)
    {
        $this->parentEntity = $parentEntity;
        parent::__construct($http);
    }

    public function find($id): Entities\Note
    {
        return parent::find($id);
    }

    public function entity()
    {
        return new Entities\Note([], $this->http, $this->parentEntity);
    }
}
