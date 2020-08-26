<?php


namespace mttzzz\AmoClient\Traits;


trait TagTrait
{
    public function tag($name = null)
    {
        if ($name) {
            $tags = is_array($name) ? $name : [$name];
            foreach ($tags as &$tag) {
                $tag = ['name' => $tag];
            }
            $this->_embedded['tags'] = $tags;
        } else {
            $this->_embedded['tags'] = $name;
        }
    }
}
