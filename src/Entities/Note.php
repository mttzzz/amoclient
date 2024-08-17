<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Traits;

class Note extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected $entity;

    public $id;

    public $entity_id;

    public $note_type;

    public $params = [];

    public function __construct($data, PendingRequest $http, $parentEntity, $entityId = null)
    {
        $this->entity = $parentEntity;
        $this->entity_id = $entityId;
        parent::__construct($data, $http);
    }

    public function common($text)
    {
        $this->params = compact('text');

        return $this->createNote(__FUNCTION__);
    }

    public function callIn($uniq, $duration, $link, $phone, $source = 'ASTERISK')
    {
        $this->params = compact('uniq', 'duration', 'link', 'phone', 'source');

        return $this->createNote(__FUNCTION__);
    }

    public function callOut($uniq, $duration, $link, $phone, $source = 'ASTERISK')
    {
        $this->params = compact('uniq', 'duration', 'link', 'phone', 'source');

        return $this->createNote(__FUNCTION__);
    }

    public function serviceMessage($text = 'Текст для примечания', $service = 'Сервис для примера')
    {
        $this->params = compact('service', 'text');

        return $this->createNote(__FUNCTION__);
    }

    public function messageCashier($status, $text)
    {
        $this->params = compact('status', 'text'); //created, shown, canceled

        return $this->createNote(__FUNCTION__);
    }

    public function invoicePaid($text, $service, $icon_url)
    {
        $this->params = compact('text', 'service', 'icon_url');

        return $this->createNote(__FUNCTION__);
    }

    public function geolocation($text, $address, $longitude, $latitude)
    {
        $this->params = compact('text', 'address', 'longitude', 'latitude');

        return $this->createNote(__FUNCTION__);
    }

    public function smsIn($text, $phone)
    {
        $this->params = compact('text', 'phone');

        return $this->createNote(__FUNCTION__);
    }

    public function smsOut($text, $phone)
    {
        $this->params = compact('text', 'phone');

        return $this->createNote(__FUNCTION__);
    }

    private function createNote($type)
    {
        $this->note_type = Str::snake($type);

        return $this->create();
    }
}
