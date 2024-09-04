<?php

namespace mttzzz\AmoClient\Traits;

trait TagTrait
{
    /**
     * @param  string|string[]|null  $name
     */
    public function tag(string|array|null $name = null): void
    {
        if (is_array($name)) {
            $tags = array_map(fn (string $tag): array => ['name' => $tag], $name);
            $this->_embedded['tags'] = $tags;
        } elseif (is_string($name)) {
            $this->_embedded['tags'] = [['name' => $name]];
        } else {
            $this->_embedded['tags'] = $name;
        }
    }
}
