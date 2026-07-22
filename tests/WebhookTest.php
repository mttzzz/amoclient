<?php

namespace mttzzz\AmoClient\Tests;

class WebhookTest extends BaseAmoClient
{
    public function test_webhook()
    {
        /* webhooks адресуются строкой destination, числового id у сущности нет — контракт
         * track() расширен лидом до int|string специально под этот случай (маркер тоже
         * кладём в тот же destination — он и есть адресуемый ключ). */
        $destination = $this->marked('https://webhook.site/a895608c-8b4a-453e-8359-4ed5d42bb454');
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
        /* трекаем сразу после subscribe(), до ассертов ниже: упавший assert между subscribe()
         * и unSubscribe() (не в try/finally) оставил бы живую подписку на боевом аккаунте. */
        $this->track('webhooks', $destination);
        $find = $this->amoClient->webhooks->find($destination);
        $this->assertEquals($find->destination, $destination);
        $unsubscribe = $entity->unSubscribe();
        /* unSubscribe() переведён с null на bool (lib-delete-2) — той же причине, что и
         * Source::delete(): null не отличал бы реальный снос от молчаливого no-op. */
        $this->assertTrue($unsubscribe);
        /* снёс сам — снял с учёта: иначе реестр держит destination, которого уже нет. */
        self::registry()->forget('webhooks', $destination);

        $empty = $this->amoClient->webhooks->find('asdasdasdd');
        $this->assertEmpty($empty->toArray());
    }
}
