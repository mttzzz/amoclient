<?php

namespace mttzzz\AmoClient\Traits\Filter;

trait Lead
{
    public function filterPrice(int $from, int $to): self
    {
        $this->filter['price'] = compact('from', 'to');

        return $this;
    }

    public function filterStatuses(array $data): self
    {
        $filter = [];
        foreach ($data as $pipelineId => $statuses) {
            foreach ($statuses as $statusId) {
                $filter[] = ['status_id' => $statusId, 'pipeline_id' => $pipelineId];
            }
        }
        $this->filter['statuses'] = $filter;

        return $this;
    }

    public function filterPipelines($pipelines): self
    {
        $this->filter['pipeline_id'] = $pipelines;

        return $this;
    }

    public function filterClosedAt(int $from, int $to): self
    {
        $this->filter['closed_at'] = compact('from', 'to');

        return $this;
    }
}
