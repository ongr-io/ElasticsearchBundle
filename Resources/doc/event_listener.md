# Event Listener

## Creating an Event Listener

You can attach event listeners to any of the events dispatched during the `ONGRElasticsearchBundle` manager query formation cycle. The name of each of the events is defined as a constant on the `ONGRElasticsearchEvents` class. Each event has their own event object:

Name | Constant | Argument passed to the listener
--- | --- | ---
`es.pre_index` | *ONGRElasticsearchEvents::PRE_INDEX* | **OperationEvent**
`es.pre_create` | *ONGRElasticsearchEvents::PRE_CREATE* | **OperationEvent**
`es.pre_update` | *ONGRElasticsearchEvents::PRE_UPDATE* | **OperationEvent**
`es.pre_delete` | *ONGRElasticsearchEvents::PRE_DELETE* | **OperationEvent**
`es.pre_commit` | *ONGRElasticsearchEvents::PRE_COMMIT* | **PreCommitEvent**

## The Listener Class
For example, **OperationEvent** listener might look like this:
```php
<?php

namespace AppBundle\EventListener;

use ONGR\ElasticSearchBundle\Event\OperationEvent;
// ...
class PreDeleteEventListener
{
    // ...
    public function preDelete(OperationEvent $event)
    {
        // Do your magic
    }
    // ...
}
```

## Listener Configuration
To register an event listener you just have to tag it with the appropriate name. For example, **PreDeleteEventListener** configuration might look like this:
```yml
services:
    # ...
    app_bundle.pre_delete_listener:
        class: AppBundle\EventListener\PreDeleteListener
        tags:
            - { name: kernel.event_listener, event: es.pre_delete, method: preDelete }
```
