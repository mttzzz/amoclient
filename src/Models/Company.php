<?php


namespace mttzzz\AmoClient\Models;


use mttzzz\AmoClient\Entities;

class Company extends AbstractModel
{
    protected $entity = 'contacts';
    protected $class = 'Contact';

    public function __construct($http)
    {
        parent::__construct($http);
    }

    public function find($id): Entities\Company
    {
        return parent::find($id);
    }

    public function note()
    {
        return new Note($this->http, $this->entity);
    }

    public function entity()
    {
        return new Entities\Company([], $this->http);
    }
}
