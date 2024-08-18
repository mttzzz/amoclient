<?php

namespace mttzzz\AmoClient\Entities;

use mttzzz\AmoClient\Traits;

class Source extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected string $entity = 'sources';

    public ?string $name = null;

    public ?int $pipeline_id = null;

    public ?string $external_id = null;

    public bool $default = false;

    public ?string $origin_code = null;

    /**
     * @var Service[]
     */
    public array $services = [];
}

class Service
{
    public string $type;

    public ?ServiceParams $params = null;

    /**
     * @var ServicePage[]
     */
    public array $pages = [];

    /**
     * @param  ServicePage[]  $pages
     */
    public function __construct(string $type, ?ServiceParams $params = null, array $pages = [])
    {
        $this->type = $type;
        $this->params = $params;
        $this->pages = $pages;
    }
}

class ServiceParams
{
    public ?bool $waba = null;

    public function __construct(?bool $waba = null)
    {
        $this->waba = $waba;
    }
}

class ServicePage
{
    public string $name;

    public string $id;

    public string $link;

    public function __construct(string $name, string $id, string $link)
    {
        $this->name = $name;
        $this->id = $id;
        $this->link = $link;
    }
}
