<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class Unsorted extends AbstractModel
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'leads/unsorted';
    }

    public function sip(): Entities\Unsorted\Sip
    {
        return new Entities\Unsorted\Sip($this->http);
    }

    public function form(): Entities\Unsorted\Form
    {
        return new Entities\Unsorted\Form($this->http);
    }

    /**
     * @return array<mixed>
     */
    public function decline(string $uid, ?int $userId = null): array
    {
        $data = [];
        if ($userId) {
            $data['user_id'] = $userId;
        }
        try {
            return $this->http->delete("{$this->entity}/{$uid}/decline", $data)->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @return array<mixed>
     */
    public function accept(string $uid, ?int $userId = null, ?int $statusId = null): array
    {
        $data = [];
        if ($userId) {
            $data['user_id'] = $userId;
        }
        if ($statusId) {
            $data['status_id'] = $statusId;
        }
        try {
            return $this->http->post("{$this->entity}/{$uid}/accept", $data)->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @param  string|array<string>  $Uid
     */
    public function filterUid(string|array $Uid): self
    {
        if (is_array($Uid)) {
            $this->filter['uid'] = $Uid;
        } else {
            $this->filter['uid'] = (string) $Uid;
        }

        return $this;
    }

    public function filterCategorySip(): self
    {
        $this->filter['category'][] = 'sip';

        return $this;
    }

    public function filterCategoryMail(): self
    {
        $this->filter['category'][] = 'mail';

        return $this;
    }

    public function filterCategoryForms(): self
    {
        $this->filter['category'][] = 'forms';

        return $this;
    }

    public function filterCategoryChats(): self
    {
        $this->filter['category'][] = 'chats';

        return $this;
    }

    public function filterPipelineId(int $pipelineId): self
    {
        $this->filter['pipeline_id'] = (int) $pipelineId;

        return $this;
    }

    public function orderCreatedAtAsc(): self
    {
        $this->order['created_at'] = 'asc';

        return $this;
    }

    public function orderCreatedAtDesc(): self
    {
        $this->order['created_at'] = 'desc';

        return $this;
    }

    public function orderUpdatedAtAsc(): self
    {
        $this->order['updated_at'] = 'asc';

        return $this;
    }

    public function orderUpdatedAtDesc(): self
    {
        $this->order['updated_at'] = 'desc';

        return $this;
    }
}
