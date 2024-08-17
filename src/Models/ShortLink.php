<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

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

    public function create(array $entities)
    {
        try {
            if (! empty($entities)) {
                return $this->http->post($this->entity, $this->prepareEntities($entities))->throw()->json();
            }

            return [];

        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
