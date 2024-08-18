<?php

namespace mttzzz\AmoClient\Traits;

trait QueryTrait
{
    public function query(string $query): self
    {
        $this->query = $query;

        return $this;
    }
}
