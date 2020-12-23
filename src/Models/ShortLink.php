<?php


namespace mttzzz\AmoClient\Models;


use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;

class ShortLink extends AbstractModel
{
    protected $entity = 'short_links';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function entity()
    {
        return new Entities\ShortLink($this->http);
    }
}
