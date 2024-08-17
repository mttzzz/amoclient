<?php

namespace mttzzz\AmoClient\Entities;

use mttzzz\AmoClient\Traits;

class Pipeline extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\StatusTrait;

    protected $entity = 'leads/pipelines';

    protected $_embedded = ['statuses' => []];

    public $name;

    public $sort = 1;

    public $is_main = false;

    public $is_unsorted_on = true;

    public function statuses()
    {
        return collect($this->_embedded['statuses']);
    }
}
