<?php


namespace mttzzz\AmoClient\Entities\Unsorted;


use Illuminate\Http\Client\PendingRequest;

class Form extends AbstractUnsorted
{
    public string $form_id, $form_name, $form_page, $ip, $referer;
    public int $form_sent_at;

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity .= '/forms';
    }

    public function addMetadata($source_uid, $source_name,
                                $form_id, $form_name, $form_page, $ip, $form_sent_at, $referer,
                                $pipeline_id = null, $created_at = null)
    {
        $this->source_uid = $source_uid;
        $this->source_name = $source_name;

        if ($pipeline_id) {
            $this->pipeline_id = $pipeline_id;
        }

        if ($created_at) {
            $this->created_at = $created_at;
        }
        $this->metadata = compact('form_id', 'form_name', 'form_page', 'ip', 'form_sent_at', 'referer');
        return $this;
    }
}
