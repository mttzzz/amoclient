<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Traits;

class Note extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected string $entity;

    public int $id;

    public int $entity_id;

    public string $note_type;

    /**
     * @var array<string, mixed>
     */
    public array $params = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data, PendingRequest $http, string $parentEntity, ?int $entityId = null)
    {
        $this->entity = $parentEntity;
        $this->entity_id = $entityId;
        parent::__construct($data, $http);
    }

    public function common(string $text): Response
    {
        $this->params = compact('text');

        return $this->createNote(__FUNCTION__);
    }

    public function callIn(string $uniq, int $duration, string $link, string $phone, string $source = 'ASTERISK'): Response
    {
        $this->params = compact('uniq', 'duration', 'link', 'phone', 'source');

        return $this->createNote(__FUNCTION__);
    }

    public function callOut(string $uniq, int $duration, string $link, string $phone, string $source = 'ASTERISK'): Response
    {
        $this->params = compact('uniq', 'duration', 'link', 'phone', 'source');

        return $this->createNote(__FUNCTION__);
    }

    public function serviceMessage(string $text = 'Текст для примечания', string $service = 'Сервис для примера'): Response
    {
        $this->params = compact('service', 'text');

        return $this->createNote(__FUNCTION__);
    }

    public function messageCashier(string $status, string $text): Response
    {
        $this->params = compact('status', 'text'); //created, shown, canceled

        return $this->createNote(__FUNCTION__);
    }

    public function invoicePaid(string $text, string $service, string $icon_url): Response
    {
        $this->params = compact('text', 'service', 'icon_url');

        return $this->createNote(__FUNCTION__);
    }

    public function geolocation(string $text, string $address, string $longitude, string $latitude): Response
    {
        $this->params = compact('text', 'address', 'longitude', 'latitude');

        return $this->createNote(__FUNCTION__);
    }

    public function smsIn(string $text, string $phone): Response
    {
        $this->params = compact('text', 'phone');

        return $this->createNote(__FUNCTION__);
    }

    public function smsOut(string $text, string $phone): Response
    {
        $this->params = compact('text', 'phone');

        return $this->createNote(__FUNCTION__);
    }

    private function createNote(string $type): Response
    {
        $this->note_type = Str::snake($type);

        return $this->create();
    }
}
