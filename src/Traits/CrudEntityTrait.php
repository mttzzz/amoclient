<?php

namespace mttzzz\AmoClient\Traits;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use stdClass;

trait CrudEntityTrait
{
    public ?int $created_at;

    public ?int $updated_at;

    public int $account_id;

    /**
     * @return array<mixed>
     *
     * @throws AmoCustomException
     */
    public function update(): array
    {
        try {
            $result = $this->http->patch($this->entity, [$this->toArray()])->throw()->json();

            return is_array($result) ? $result : [];
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @return array<mixed>
     *
     * @throws AmoCustomException
     */
    public function create(): array
    {
        try {
            $result = $this->http->post($this->entity, [$this->toArray()])->throw()->json();

            return is_array($result) ? $result : [];
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @throws AmoCustomException
     */
    public function createGetId(): int
    {
        $embedded = $this->create()['_embedded'] ?? null;
        $items = is_array($embedded) ? ($embedded[$this->entity] ?? null) : null;
        $item = is_array($items) ? ($items[0] ?? null) : null;
        $id = is_array($item) ? ($item['id'] ?? null) : null;

        return is_numeric($id) ? (int) $id : 0;
    }

    public function setResponsibleUser(int $accountId, int $id): void
    {
        $isExist = DB::connection('octane')
            ->table('account_amo_user')
            ->where('amo_user_id', $id)
            ->where('is_active', true)
            ->where('account_id', $accountId)
            ->exists();

        $this->responsible_user_id = $isExist ? $id : 0;
    }

    public function getCreatedAt(): Carbon
    {
        return Carbon::parse($this->created_at);
    }

    public function getResponsibleName(): ?string
    {
        if ($this->responsible_user_id === 0) {
            return null;
        }

        $user = DB::connection('octane')->table('amo_users')->find($this->responsible_user_id);

        if (! $user instanceof stdClass) {
            return null;
        }

        $name = $user->name ?? null;

        return is_string($name) ? $name : null;
    }
}
