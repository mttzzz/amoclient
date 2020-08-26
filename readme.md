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



