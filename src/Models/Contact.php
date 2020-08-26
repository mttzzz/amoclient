<?php


namespace mttzzz\AmoClient\Models;

use mttzzz\AmoClient\Entities;

class Contact extends AbstractModel
{
    protected $entity = 'contacts';

    public function __construct($http)
    {
        parent::__construct($http);
    }

    public function find($id): Entities\Contact
    {
        return parent::find($id);
    }

    public function note()
    {
        return new Note($this->http, $this->entity);
    }

    public function entity()
    {
        return new Entities\Contact([], $this->http);
    }
}
