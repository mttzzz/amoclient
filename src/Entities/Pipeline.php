<?php


namespace mttzzz\AmoClient\Entities;

use mttzzz\AmoClient\Traits;

class Pipeline extends AbstractEntity
{
    use Traits\StatusTrait, Traits\CrudEntityTrait;

    protected $entity = 'leads/pipelines', array $_embedded = ['statuses' => []];
    public $name, $sort = 1, $is_main = false, $is_unsorted_on = true;

    public function statuses()
    {
        return collect($this->_embedded['statuses']);
    }
}
