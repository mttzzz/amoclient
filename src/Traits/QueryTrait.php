<?php

namespace mttzzz\AmoClient\Traits;

trait QueryTrait
{
    public function query($query)
    {
        $this->query = $query;

        return $this;
    }
}
