<?php

namespace mttzzz\AmoClient\Traits;

trait StatusTrait
{
    public function addStatus($name, $sort, $color)
    {
        $color = in_array($color, ['#ffff99', '#99ccff', '#ffcc66']) ? '#d6eaff' : $color;
        $this->_embedded['statuses'][] = compact('name', 'sort', 'color');

        return $this;
    }

    public function changeSuccessStatus($name)
    {
        $this->_embedded['statuses'][] = ['name' => $name, 'id' => 142];

        return $this;
    }

    public function changeFailStatus($name)
    {
        $this->_embedded['statuses'][] = ['name' => $name, 'id' => 143];

        return $this;
    }
}
