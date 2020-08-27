<?php


namespace mttzzz\AmoClient\Models;


class Account extends AbstractModel
{
    protected $http;
    protected $entity = 'account';
    public $with = [];

    public function __construct($http)
    {
        parent::__construct($http);
    }

    public function withAmojoId()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withAmojoRights()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withUsersGroups()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withTaskTypes()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withVersion()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withEntityNames()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withDatetimeSettings()
    {
        return $this->addWith(__FUNCTION__);
    }
}
