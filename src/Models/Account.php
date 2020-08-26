<?php


namespace mttzzz\AmoClient\Models;


class Account
{
    protected $http;

    public $with = [];

    public function __construct($http)
    {
        $this->http = $http;
    }

    public function get(): array
    {
        return $this->http->get('account', ['with' => implode(',', $this->with)])
            ->throw()->json();
    }

    public function withAmojoId()
    {
        $this->with[] = 'amojo_id';
        return $this;
    }

    public function withAmojoRights()
    {
        $this->with[] = 'amojo_rights';
        return $this;
    }

    public function withUsersGroups()
    {
        $this->with[] = 'users_groups';
        return $this;
    }

    public function withTaskTypes()
    {
        $this->with[] = 'task_types';
        return $this;
    }

    public function withVersion()
    {
        $this->with[] = 'version';
        return $this;
    }

    public function withEntityNames()
    {
        $this->with[] = 'entity_names';
        return $this;
    }

    public function withDateTimeSettings()
    {
        $this->with[] = 'datetime_settings';
        return $this;
    }
}
