<?php

namespace mttzzz\AmoClient\Traits;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

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
            return $this->http->patch($this->entity, [$this->toArray()])->throw()->json();
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
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @throws AmoCustomException
     */
    public function createGetId(): int
    {
        return $this->create()['_embedded'][$this->entity][0]['id'];
    }

    // /**
    //  * @return array<mixed>
    //  *
    //  * @throws AmoCustomException
    //  */
    // public function delete(): array
    // {
    //     try {
    //         return $this->http->delete($this->entity.'/'.$this->id)->throw()->json();
    //     } catch (ConnectionException|RequestException $e) {
    //         throw new AmoCustomException($e);
    //     }
    // }

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

        return $user->name;
    }
}
