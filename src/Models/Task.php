<?php


namespace mttzzz\AmoClient\Models;

use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits\CrudTrait;
use mttzzz\AmoClient\Traits\QueryTrait;

class Task extends AbstractModel
{
    use CrudTrait, QueryTrait;

    protected $entity = 'tasks';
    protected $class = 'Task';

    public function __construct($http)
    {
        parent::__construct($http);
    }

    public function find($id): Entities\Task
    {
        return parent::find($id);
    }
}
