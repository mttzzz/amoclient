<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class Webhook extends AbstractEntity
{
    protected string $entity = 'webhooks';

    protected $settings = [];

    public $disabled;

    public $destination;

    public $sort;

    public function subscribe()
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

    public function unSubscribe()
    {
        try {
            return $this->http->delete($this->entity, ['destination' => $this->destination])->throw();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function responsibleLead()
    {
        $this->settings[] = 'responsible_lead';

        return $this;
    }

    public function responsibleContact()
    {
        $this->settings[] = 'responsible_contact';

        return $this;
    }

    public function responsibleCompany()
    {
        $this->settings[] = 'responsible_company';

        return $this;
    }

    public function responsibleCustomer()
    {
        $this->settings[] = 'responsible_customer';

        return $this;
    }

    public function responsibleTask()
    {
        $this->settings[] = 'responsible_task';

        return $this;
    }

    public function restoreLead()
    {
        $this->settings[] = 'restore_lead';

        return $this;
    }

    public function restoreContact()
    {
        $this->settings[] = 'restore_contact';

        return $this;
    }

    public function restoreCompany()
    {
        $this->settings[] = 'restore_company';

        return $this;
    }

    public function addLead()
    {
        $this->settings[] = 'add_lead';

        return $this;
    }

    public function addContact()
    {
        $this->settings[] = 'add_contact';

        return $this;
    }

    public function addCompany()
    {
        $this->settings[] = 'add_company';

        return $this;
    }

    public function addCustomer()
    {
        $this->settings[] = 'add_customer';

        return $this;
    }

    public function addTask()
    {
        $this->settings[] = 'add_task';

        return $this;
    }

    public function updateLead()
    {
        $this->settings[] = 'update_lead';

        return $this;
    }

    public function updateContact()
    {
        $this->settings[] = 'update_contact';

        return $this;
    }

    public function updateCompany()
    {
        $this->settings[] = 'update_company';

        return $this;
    }

    public function updateCustomer()
    {
        $this->settings[] = 'update_customer';

        return $this;
    }

    public function updateTask()
    {
        $this->settings[] = 'update_task';

        return $this;
    }

    public function deleteLead()
    {
        $this->settings[] = 'delete_lead';

        return $this;
    }

    public function deleteContact()
    {
        $this->settings[] = 'delete_contact';

        return $this;
    }

    public function deleteCompany()
    {
        $this->settings[] = 'delete_company';

        return $this;
    }

    public function deleteCustomer()
    {
        $this->settings[] = 'delete_customer';

        return $this;
    }

    public function deleteTask()
    {
        $this->settings[] = 'delete_task';

        return $this;
    }

    public function statusLead()
    {
        $this->settings[] = 'status_lead';

        return $this;
    }

    public function noteLead()
    {
        $this->settings[] = 'note_lead';

        return $this;
    }

    public function noteContact()
    {
        $this->settings[] = 'note_contact';

        return $this;
    }

    public function noteCompany()
    {
        $this->settings[] = 'note_company';

        return $this;
    }

    public function noteCustomer()
    {
        $this->settings[] = 'note_customer';

        return $this;
    }
}
