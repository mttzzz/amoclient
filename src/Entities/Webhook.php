<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class Webhook extends AbstractEntity
{
    protected string $entity = 'webhooks';

    /**
     * @var string[] // Указываем, что массив содержит строки
     */
    protected array $settings = [];

    public bool $disabled;

    public string $destination;

    public int $sort;

    /**
     * @return array<mixed>
     */
    public function subscribe(): array
    {
        try {
            return $this->http->post($this->entity, [
                'destination' => $this->destination,
                'settings' => $this->settings,
            ])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function unSubscribe(): null
    {
        try {
            return $this->http->delete($this->entity, ['destination' => $this->destination])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function responsibleLead(): self
    {
        $this->settings[] = 'responsible_lead';

        return $this;
    }

    public function responsibleContact(): self
    {
        $this->settings[] = 'responsible_contact';

        return $this;
    }

    public function responsibleCompany(): self
    {
        $this->settings[] = 'responsible_company';

        return $this;
    }

    public function responsibleCustomer(): self
    {
        $this->settings[] = 'responsible_customer';

        return $this;
    }

    public function responsibleTask(): self
    {
        $this->settings[] = 'responsible_task';

        return $this;
    }

    public function restoreLead(): self
    {
        $this->settings[] = 'restore_lead';

        return $this;
    }

    public function restoreContact(): self
    {
        $this->settings[] = 'restore_contact';

        return $this;
    }

    public function restoreCompany(): self
    {
        $this->settings[] = 'restore_company';

        return $this;
    }

    public function addLead(): self
    {
        $this->settings[] = 'add_lead';

        return $this;
    }

    public function addContact(): self
    {
        $this->settings[] = 'add_contact';

        return $this;
    }

    public function addCompany(): self
    {
        $this->settings[] = 'add_company';

        return $this;
    }

    public function addCustomer(): self
    {
        $this->settings[] = 'add_customer';

        return $this;
    }

    public function addTask(): self
    {
        $this->settings[] = 'add_task';

        return $this;
    }

    public function updateLead(): self
    {
        $this->settings[] = 'update_lead';

        return $this;
    }

    public function updateContact(): self
    {
        $this->settings[] = 'update_contact';

        return $this;
    }

    public function updateCompany(): self
    {
        $this->settings[] = 'update_company';

        return $this;
    }

    public function updateCustomer(): self
    {
        $this->settings[] = 'update_customer';

        return $this;
    }

    public function updateTask(): self
    {
        $this->settings[] = 'update_task';

        return $this;
    }

    public function deleteLead(): self
    {
        $this->settings[] = 'delete_lead';

        return $this;
    }

    public function deleteContact(): self
    {
        $this->settings[] = 'delete_contact';

        return $this;
    }

    public function deleteCompany(): self
    {
        $this->settings[] = 'delete_company';

        return $this;
    }

    public function deleteCustomer(): self
    {
        $this->settings[] = 'delete_customer';

        return $this;
    }

    public function deleteTask(): self
    {
        $this->settings[] = 'delete_task';

        return $this;
    }

    public function statusLead(): self
    {
        $this->settings[] = 'status_lead';

        return $this;
    }

    public function noteLead(): self
    {
        $this->settings[] = 'note_lead';

        return $this;
    }

    public function noteContact(): self
    {
        $this->settings[] = 'note_contact';

        return $this;
    }

    public function noteCompany(): self
    {
        $this->settings[] = 'note_company';

        return $this;
    }

    public function noteCustomer(): self
    {
        $this->settings[] = 'note_customer';

        return $this;
    }
}
