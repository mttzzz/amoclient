<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

trait CrudEntityTrait
{
    public function update()
    {
        try {
            return $this->http->patch($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function create()
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function createGetId()
    {
        return $this->create()['_embedded'][$this->entity][0]['id'];
    }

    public function delete()
    {
        try {
            return $this->http->delete($this->entity . '/' . $this->id)->throw()->json();
            return null;
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function setResponsibleUser($accountId, $id)
    {
        $user = DB::connection('octane')
            ->table('account_amo_user')
            ->where('amo_user_id', $id)
            ->where('account_id', $accountId)
            ->first();
        $this->responsible_user_id = $user && $user->is_active ? $id : null;
    }
}
