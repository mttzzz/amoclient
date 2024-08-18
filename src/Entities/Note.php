<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Traits;

class Note extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    public ?int $entity_id;

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

    /**
     * @return array<mixed>
     */
    public function common(string $text): array
    {
        $this->params = compact('text');

        return $this->createNote(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    public function callIn(string $uniq, int $duration, string $link, string $phone, string $source = 'ASTERISK'): array
    {
        $this->params = compact('uniq', 'duration', 'link', 'phone', 'source');

        return $this->createNote(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    public function callOut(string $uniq, int $duration, string $link, string $phone, string $source = 'ASTERISK'): array
    {
        $this->params = compact('uniq', 'duration', 'link', 'phone', 'source');

        return $this->createNote(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    public function serviceMessage(string $text = 'Текст для примечания', string $service = 'Сервис для примера'): array
    {
        $this->params = compact('service', 'text');

        return $this->createNote(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    public function messageCashier(string $status, string $text): array
    {
        $this->params = compact('status', 'text'); //created, shown, canceled

        return $this->createNote(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    public function invoicePaid(string $text, string $service, string $icon_url): array
    {
        $this->params = compact('text', 'service', 'icon_url');

        return $this->createNote(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    public function geolocation(string $text, string $address, string $longitude, string $latitude): array
    {
        $this->params = compact('text', 'address', 'longitude', 'latitude');

        return $this->createNote(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    public function smsIn(string $text, string $phone): array
    {
        $this->params = compact('text', 'phone');

        return $this->createNote(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    public function smsOut(string $text, string $phone): array
    {
        $this->params = compact('text', 'phone');

        return $this->createNote(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    private function createNote(string $type): array
    {
        $this->note_type = Str::snake($type);

        return $this->create();
    }
}
