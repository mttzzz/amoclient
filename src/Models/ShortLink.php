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
     * @param  list<Entities\AbstractEntity>  $entities
     * @return array<mixed>
     */
    public function create(array $entities): array
    {
        try {
            if (! empty($entities)) {
                $result = $this->http->post($this->entity, $this->prepareEntities($entities))->throw()->json();

                return is_array($result) ? $result : [];
            }

            return [];
            // @codeCoverageIgnoreStart
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
            // @codeCoverageIgnoreEnd
        }
    }
}
