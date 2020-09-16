<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class Unsorted extends AbstractModel
{
    protected $entity = 'leads/unsorted';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function sip()
    {
        return new Entities\Unsorted\Sip($this->http);
    }

    public function form()
    {
        return new Entities\Unsorted\Form($this->http);
    }

    public function decline($uid, $userId = null)
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

    public function accept($uid, $userId = null, $statusId = null)
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

    public function filterUid($Uid)
    {
        $this->filter['uid'] = is_array($Uid) ? $Uid : (string)$Uid;
        return $this;
    }

    public function filterCategorySip()
    {
        $this->filter['category'][] = 'sip';
        return $this;
    }

    public function filterCategoryMail()
    {
        $this->filter['category'][] = 'mail';
        return $this;
    }

    public function filterCategoryForms()
    {
        $this->filter['category'][] = 'forms';
        return $this;
    }

    public function filterCategoryChats()
    {
        $this->filter['category'][] = 'chats';
        return $this;
    }

    public function filterPipelineId($pipelineId)
    {
        $this->filter['pipeline_id'] = (int)$pipelineId;
        return $this;
    }

    public function orderCreatedAtAsc()
    {
        $this->order['created_at'] = 'asc';
        return $this;
    }

    public function orderCreatedAtDesc()
    {
        $this->order['created_at'] = 'desc';
        return $this;
    }

    public function orderUpdatedAtAsc()
    {
        $this->order['updated_at'] = 'asc';
        return $this;
    }

    public function orderUpdatedAtDesc()
    {
        $this->order['updated_at'] = 'desc';
        return $this;
    }
}
