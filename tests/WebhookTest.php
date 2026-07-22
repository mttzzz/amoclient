<?php

namespace mttzzz\AmoClient\Tests;

class WebhookTest extends BaseAmoClient
{
    public function test_webhook()
    {
        /* webhooks в amo адресуются строкой destination, числового id у сущности нет
         * (Entities\Webhook — ни find(), ни unSubscribe() не оперируют id). Контракт
         * track(string $type, int $id) под это не подходит — трекать нечем, а не забыто:
         * тот же гап независимо всплыл у teardown-core и lib-delete (см. их .tmp/scratch/sp0/*.md),
         * ждём от лида решения по механизму для строково-адресуемых сущностей. */
        $destination = 'https://webhook.site/a895608c-8b4a-453e-8359-4ed5d42bb454';
        $entity = $this->amoClient->webhooks->entity($destination);
        $entity->responsibleLead();
        $entity->responsibleContact();
        $entity->responsibleCompany();
        $entity->responsibleCustomer();
        $entity->responsibleTask();
        $entity->restoreLead();
        $entity->restoreContact();
        $entity->restoreCompany();
        $entity->addLead();
        $entity->addContact();
        $entity->addCompany();
        $entity->addCustomer();
        $entity->addTask();
        $entity->updateLead();
        $entity->updateContact();
        $entity->updateCompany();
        $entity->updateCustomer();
        $entity->updateTask();
        $entity->deleteLead();
        $entity->deleteContact();
        $entity->deleteCompany();
        $entity->deleteCustomer();
        $entity->deleteTask();
        $entity->statusLead();
        $entity->noteLead();
        $entity->noteContact();
        $entity->noteCompany();
        $entity->noteCustomer();
        $entity->subscribe();
        $find = $this->amoClient->webhooks->find($destination);
        $this->assertEquals($find->destination, $destination);
        $unsubscribe = $entity->unSubscribe();
        $this->assertNull($unsubscribe);

        $empty = $this->amoClient->webhooks->find('asdasdasdd');
        $this->assertEmpty($empty->toArray());
    }
}
