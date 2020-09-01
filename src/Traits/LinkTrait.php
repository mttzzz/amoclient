<?php


namespace mttzzz\AmoClient\Traits;


trait LinkTrait
{
    public function getLinks()
    {
        return $data = $this->http->get($this->entity . '/' . $id, ['with' => implode(',', $this->with)])
                ->throw()->json() ?? [];
    }
}
