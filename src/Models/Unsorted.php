<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

/**
 * Неразобранное.
 *
 * СОРТИРОВКИ У ЭТОГО РОУТА НЕТ — и её методов здесь тоже нет, намеренно. Замер
 * (§9.7): `order[created_at]=asc`, `order[created_at]=desc` и запрос вообще без
 * параметра дают одну и ту же выдачу. Раньше модель поставляла
 * `orderCreatedAtAsc()` и `orderCreatedAtDesc()`; первый был тихим no-op, а
 * второй «работал» случайно — просто совпадал с порядком по умолчанию.
 *
 * Порядок по умолчанию: ОТ НОВЫХ К СТАРЫМ (`created_at` убывает). На него
 * можно рассчитывать при обходе, но задать другой нечем.
 */
class Unsorted extends AbstractModel
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'leads/unsorted';
    }

    public function sip(): Entities\Unsorted\Sip
    {
        return new Entities\Unsorted\Sip($this->http);
    }

    public function form(): Entities\Unsorted\Form
    {
        return new Entities\Unsorted\Form($this->http);
    }

    /**
     * @return array<mixed>
     */
    public function decline(string $uid, ?int $userId = null): array
    {
        $data = [];
        if ($userId !== null) {
            $data['user_id'] = $userId;
        }
        try {
            $result = $this->http->delete("{$this->entity}/{$uid}/decline", $data)->throw()->json();

            return is_array($result) ? $result : [];
            // @codeCoverageIgnoreStart
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @return array<mixed>
     */
    public function accept(string $uid, ?int $userId = null, ?int $statusId = null): array
    {
        $data = [];
        if ($userId !== null) {
            $data['user_id'] = $userId;
        }
        if ($statusId !== null) {
            $data['status_id'] = $statusId;
        }
        try {
            $result = $this->http->post("{$this->entity}/{$uid}/accept", $data)->throw()->json();

            return is_array($result) ? $result : [];
            // @codeCoverageIgnoreStart
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param  string|array<string>  $Uid
     */
    public function filterUid(string|array $Uid): self
    {
        if (is_array($Uid)) {
            $this->filter['uid'] = $Uid;
        } else {
            $this->filter['uid'] = (string) $Uid;
        }

        return $this;
    }

    public function filterCategorySip(): self
    {
        $this->addFilterCategory('sip');

        return $this;
    }

    public function filterCategoryMail(): self
    {
        $this->addFilterCategory('mail');

        return $this;
    }

    public function filterCategoryForms(): self
    {
        $this->addFilterCategory('forms');

        return $this;
    }

    public function filterCategoryChats(): self
    {
        $this->addFilterCategory('chats');

        return $this;
    }

    /**
     * $this->filter — array<string, mixed>, значение по 'category' для
     * phpstan mixed, поэтому не аппендим напрямую (offsetAccess на mixed),
     * а гардим is_array() перед [].
     */
    private function addFilterCategory(string $category): void
    {
        $categories = $this->filter['category'] ?? [];
        $categories = is_array($categories) ? $categories : [];
        $categories[] = $category;
        $this->filter['category'] = $categories;
    }

    public function filterPipelineId(int $pipelineId): self
    {
        $this->filter['pipeline_id'] = (int) $pipelineId;

        return $this;
    }
}
