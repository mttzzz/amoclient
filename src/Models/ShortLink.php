<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class ShortLink extends AbstractModel
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'short_links';
    }

    public function entity(): Entities\ShortLink
    {
        return new Entities\ShortLink($this->http);
    }

    /**
     * @param  array<mixed>  $entities
     * @return array<mixed>
     */
    public function create(array $entities): array
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
