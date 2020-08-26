<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Http\Client\RequestException;

trait ElementTrait
{
    public function elements($data = [])
    {
        try {
            $entities = $this->http->get("catalogs/{$this->id}/elements", $data)
                ->throw()->json();
            return $entities['_embedded']['elements'] ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function addElements($elements)
    {
        try {
            $entities = $this->http->post("catalogs/{$this->id}/elements", $elements)
                ->throw()->json();
            return $entities['_embedded']['elements'] ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function updateElements($elements)
    {
        try {
            $entities = $this->http->patch("catalogs/{$this->id}/elements", $elements)
                ->throw()->json();
            return $entities['_embedded']['elements'] ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }
}
