<?php


namespace mttzzz\AmoClient\Models;


use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities\Unsorted\Form;
use mttzzz\AmoClient\Entities\Unsorted\Sip;

class Unsorted extends AbstractModel
{
    protected $entity = 'leads/unsorted';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function sip()
    {
        return new Sip($this->http);
    }

    public function form()
    {
        return new Form($this->http);
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
        $this->filter['order']['created_at'] = 'asc';
        return $this;
    }

    public function orderCreatedAtDesc()
    {
        $this->filter['order']['created_at'] = 'desc';
        return $this;
    }

    public function orderUpdatedAtAsc()
    {
        $this->filter['order']['updated_at'] = 'asc';
        return $this;
    }

    public function orderUpdatedAtDesc()
    {
        $this->filter['order']['updated_at'] = 'desc';
        return $this;
    }
}
