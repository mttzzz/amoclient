<?php

namespace mttzzz\AmoClient\Traits;

trait StatusTrait
{
    public function addStatus(string $name, int $sort, string $color): self
    {
        $color = in_array($color, ['#ffff99', '#99ccff', '#ffcc66']) ? '#d6eaff' : $color;
        $this->_embedded['statuses'][] = compact('name', 'sort', 'color');

        return $this;
    }

    public function changeSuccessStatus(string $name): self
    {
        $this->_embedded['statuses'][] = ['name' => $name, 'id' => 142];

        return $this;
    }

    public function changeFailStatus(string $name): self
    {
        $this->_embedded['statuses'][] = ['name' => $name, 'id' => 143];

        return $this;
    }
}
