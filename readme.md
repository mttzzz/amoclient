# AmoClient

[![Latest Version on Packagist][ico-version]][link-packagist]


Клиент для amoCRM. 

# Installation

Via Composer

``` bash
$ composer require mttzzz/amoclient
```

#Usage
``` php
$amo = new AmoClient($key);
```
##Account
### get
https://www.amocrm.ru/developers/content/crm_platform/account-info#with-96b1f0be-e739-4716-85ad-ec1799c5a11d-params
``` php
$account = $amo->account;
$account
    ->withAmojoId()
    ->withAmojoRights()
    ->withUsersGroups()
    ->withTaskTypes()
    ->withVersion()
    ->withEntityNames()
    ->withDateTimeSettings()
    ->get();
```

##Lead
### get
``` php
$leads = $amo->leads
->page(2)
->limit(10)
->query('test')
->orderByCreatedAtAsc()
->orderByCreatedAtDesc()
->orderByUpdatedAtAsc()
->orderByUpdatedAtDesc()
->orderByIdAsc()
->orderByIdDesc()
->withCatalogElements()
->withIsPriceModifiedByRobot()
->withLossReason()
->withContacts()
->withOnlyDeleted()
->get();
$lead = $amo->leads->find(27211533);
```

### createOne
``` php
$lead = $amo->leads->entity();
$lead->name = 'testLead';
$lead->create();
```

### createMany
``` php
$lead = $amo->leads->entity();
$lead->name = 'testOne1';
$lead->status_id = 21714793;
$lead2 = $amo->leads->entity();
$lead2->name = 'testOne2';
$lead2->setCF(449541, 'test');
$lead2->price = 100;
$lead2->status_id = 21714793;
$lead2->responsible_user_id = 1693807;
$data = $amo->leads->create([$lead,$lead2]);
```

### createNote
``` php
$amo->leads->entity($leadId)->notes->common($text);
$amo->leads->entity($leadId)->notes->invoicePaid($text, $service, $icon_url);
$amo->leads->entity($leadId)->notes->smsIn($text, $phone);
$amo->leads->entity($leadId)->notes->smsOut($text, $phone);
$amo->leads->entity($leadId)->notes->callIn($uniq, $duration, $link, $phone, $source = 'ASTERISK');
$amo->leads->entity($leadId)->notes->callOut($uniq, $duration, $link, $phone, $source = 'ASTERISK');
```

### createTask
``` php
$amo->leads->entity(27211595)->tasks
->add($text, $responsible_user_id = null, $completeTill = null, $duration = null, $type = 2)
$amo->leads->entity(27211595)->tasks->add('text', 1693807,Carbon::now()->addHour()->timestamp, 60 * 60, 2);
```

### updateOne
``` php
$lead = $amo->leads->entity(27211595);
$lead->name = 'testOne22';
$lead->price = 222;
$lead->responsible_user_id = 1693807;
$lead->setCF(449541, 'test');
$lead->update();
```

### updateMany
``` php
$ids = [27211595, 27211597];
$leads = [];
foreach ($ids as $id) {
    $lead = $amo->leads->entity($id);
    $lead->tag('updateMany');
    $leads[] = $lead;
    }
$amo->leads->update($leads);
```

### delete
``` php
not work
```



##Contact
### get
``` php
$leads = $amo->contacts
->page(2)
->limit(10)
->query('test')
->orderByCreatedAtAsc()
->orderByCreatedAtDesc()
->orderByUpdatedAtAsc()
->orderByUpdatedAtDesc()
->orderByIdAsc()
->orderByIdDesc()
->withCatalogElements()
->withLeads()
->withCustomers()
->get();
$contact = $amo->contacts->find(43680761);
```

### createOne
``` php
$contact = $amo->contacts->entity();
$contact->name = 'test';
$contact->create();
```

### createMany
``` php
$contact = $amo->contacts->entity();
$contact->name = 'testOne1';
$contact2 = $amo->contacts->entity();
$contact2->name = 'testOne2';
$contact2->setCF(449541, 'test');
$contact2->emailAdd('test@test.loc')->phoneAdd(3752511111111);
$contact2->responsible_user_id = 1693807;
$data = $amo->contacts->create([$contact,$contact2]);
```

### createNote
``` php
$amo->leads->entity($leadId)->notes->common($text);
$amo->leads->entity($leadId)->notes->invoicePaid($text, $service, $icon_url);
$amo->leads->entity($leadId)->notes->smsIn($text, $phone);
$amo->leads->entity($leadId)->notes->smsOut($text, $phone);
$amo->leads->entity($leadId)->notes->callIn($uniq, $duration, $link, $phone, $source = 'ASTERISK');
$amo->leads->entity($leadId)->notes->callOut($uniq, $duration, $link, $phone, $source = 'ASTERISK');
```

### createTask
``` php
$amo->leads->entity(27211595)->tasks
->add($text, $responsible_user_id = null, $completeTill = null, $duration = null, $type = 2)
$amo->leads->entity(27211595)->tasks->add('text', 1693807,Carbon::now()->addHour()->timestamp, 60 * 60, 2);
```

### updateOne
``` php
$lead = $amo->leads->entity(27211595);
$lead->name = 'testOne22';
$lead->price = 222;
$lead->responsible_user_id = 1693807;
$lead->setCF(449541, 'test');
$lead->update();
```

### updateMany
``` php
$ids = [27211595, 27211597];
$leads = [];
foreach ($ids as $id) {
    $lead = $amo->leads->entity($id);
    $lead->tag('updateMany');
    $leads[] = $lead;
    }
$amo->leads->update($leads);
```

### delete
``` php
not work
```



