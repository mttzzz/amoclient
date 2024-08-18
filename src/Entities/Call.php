<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class Call extends AbstractEntity
{
    public string $direction;

    public string $uniq;

    public string $source;

    public string $link;

    public string $phone;

    public string $call_result;

    public string $request_id;

    public int $duration = 0;

    public int $call_status;

    public int $created_by;

    public ?int $updated_by;

    public int $created_at;

    public int $updated_at;

    public function __construct($data, PendingRequest $http)
    {
        parent::__construct($data, $http);
        $this->entity = 'calls';
    }

    public function create(): Call
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function uniq(string $uniq): self
    {
        $this->uniq = $uniq;

        return $this;
    }

    public function duration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function source(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function link(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function phone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function result(string $result): self
    {
        $this->call_result = $result;

        return $this;
    }

    public function responsibleUserId(int $userId): self
    {
        $this->responsible_user_id = $userId;

        return $this;
    }

    public function createdBy(int $userId): self
    {
        $this->created_by = $userId;

        return $this;
    }

    public function updatedBy(int $userId): self
    {
        $this->updated_by = $userId;

        return $this;
    }

    public function createdAt(int $time): self
    {
        $this->created_at = $time;

        return $this;
    }

    public function updatedAt(int $time): self
    {
        $this->updated_at = $time;

        return $this;
    }

    public function requestId(string $requestId): self
    {
        $this->request_id = $requestId;

        return $this;
    }

    public function direction(string $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function directionOutbound(): self
    {
        $this->direction = 'outbound';

        return $this;
    }

    public function directionInbound(): self
    {
        $this->direction = 'inbound';

        return $this;
    }

    public function status(int $status): self
    {
        $this->call_status = $status;

        return $this;
    }

    public function statusLeaveMessage(): self
    {
        $this->call_status = 1;

        return $this;
    }

    public function statusCallLater(): self
    {
        $this->call_status = 2;

        return $this;
    }

    public function statusAbsent(): self
    {
        $this->call_status = 3;

        return $this;
    }

    public function statusSuccess(): self
    {
        $this->call_status = 4;

        return $this;
    }

    public function statusWrongNumber(): self
    {
        $this->call_status = 5;

        return $this;
    }

    public function statusError(): self
    {
        $this->call_status = 6;

        return $this;
    }

    public function statusBusy(): self
    {
        $this->call_status = 7;

        return $this;
    }
}
