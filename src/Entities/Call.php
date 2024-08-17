<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class Call extends AbstractEntity
{
    protected string $entity = 'calls';

    protected string $direction;

    protected string $uniq;

    protected string $source;

    protected string $link;

    protected string $phone;

    protected string $call_result;

    protected string $request_id;

    protected int $duration = 0;

    protected int $call_status;

    protected int $responsible_user_id;

    protected int $created_by;

    protected int $updated_by;

    protected int $created_at;

    protected int $updated_at;

    public function __construct($data, PendingRequest $http)
    {
        parent::__construct($data, $http);
    }

    public function create()
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function uniq(string $uniq)
    {
        $this->uniq = $uniq;

        return $this;
    }

    public function duration(int $duration)
    {
        $this->duration = $duration;

        return $this;
    }

    public function source(string $source)
    {
        $this->source = $source;

        return $this;
    }

    public function link(string $link)
    {
        $this->link = $link;

        return $this;
    }

    public function phone(string $phone)
    {
        $this->phone = $phone;

        return $this;
    }

    public function result(string $result)
    {
        $this->call_result = $result;

        return $this;
    }

    public function responsibleUserId(int $userId)
    {
        $this->responsible_user_id = $userId;

        return $this;
    }

    public function createdBy(int $userId)
    {
        $this->created_by = $userId;

        return $this;
    }

    public function updatedBy(int $userId)
    {
        $this->updated_by = $userId;

        return $this;
    }

    public function createdAt(int $time)
    {
        $this->created_at = $time;

        return $this;
    }

    public function updatedAt(int $time)
    {
        $this->updated_at = $time;

        return $this;
    }

    public function requestId(string $requestId)
    {
        $this->request_id = $requestId;

        return $this;
    }

    public function direction($direction)
    {
        $this->direction = $direction;

        return $this;
    }

    public function directionOutbound()
    {
        $this->direction = 'outbound';

        return $this;
    }

    public function directionInbound()
    {
        $this->direction = 'inbound';

        return $this;
    }

    public function status($status)
    {
        $this->call_status = $status;

        return $this;
    }

    public function statusLeaveMessage()
    {
        $this->call_status = 1;

        return $this;
    }

    public function statusCallLater()
    {
        $this->call_status = 2;

        return $this;
    }

    public function statusAbsent()
    {
        $this->call_status = 3;

        return $this;
    }

    public function statusSuccess()
    {
        $this->call_status = 4;

        return $this;
    }

    public function statusWrongNumber()
    {
        $this->call_status = 5;

        return $this;
    }

    public function statusError()
    {
        $this->call_status = 6;

        return $this;
    }

    public function statusBusy()
    {
        $this->call_status = 7;

        return $this;
    }
}
