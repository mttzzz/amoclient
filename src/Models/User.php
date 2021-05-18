<?php


namespace mttzzz\AmoClient\Models;


class User extends AbstractModel
{
    protected $entity = 'users';

    public function __construct($http)
    {
        parent::__construct($http);
    }

    public function withRole()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withGroup()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withUuid()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withAmojoId()
    {
        return $this->addWith(__FUNCTION__);
    }
}
