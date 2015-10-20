# Bundle usage

> To proceed with steps bellow it is necessary to read [mapping](mapping.md) topic and have defined documents in the bundle.

For all steps below we asume that there is an `AcmeDemoBundle` with the `Content` document.

```php

<?php
//AcmeDemoBundle:Content
use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\AbstractContentDocument;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @ES\Document(type="content")
 */
class Content extends AbstractContentDocument
{
    /**
     * @ES\Property(type="string")
     */
    public $title;
}

```

## Manager

Elasticsearch bundle provides managers able to handle several indexes to communicate with elasticsearch.

Once you define managers in your `config.yml` file, you can use them in controllers and grab them from DI container via `es.manager` (alias for `es.manager.default`). If you define more than one manager, for example called `foo`, then it will be accessible via `es.manager.foo`.

```php

$manager = $this->get('es.manager');

```

## Repositories

In addition manager provides repository access, which enables direct access to the elasticsearch type.  Repositories represents a documents. Whenever you need to do any action with a repository to get it:

```php

$manager = $this->get('es.manager');
$repo = $manager->getRepository('AcmeDemoBundle:Content');

```

So instead you can call just:

```php

$repo = $this->get('es.manager.default.content');

```

`default` - represents a manager name and `content` an elasticsearch type name.

> Important: Document with the certain type name has to be mapped in the manager.

You can also get a manager from the repo instance:

```php

$manager = $repo->getManager();

```

## Create a document

```php

$content = new Content();
$content->id = 5; // Optional, if not set, elasticsearch will set a random.
$content->title = 'Acme title';
$manager->persist($content);
$manager->commit();

```

> id field comes from `AbstractDocument`. It's optional, in addition you can also use `ttl`, 'parent' and other special fields.

## Update a document

```php

$repo = $this->get('es.manager.default.content');
$content = $repo->find(5);
$content->title = 'changed Acme title';
$manager->persist($content);
$manager->commit();

```

## Delete a document

```php

$repo = $this->get('es.manager.default.content');
$content = $repo->delete(5);

```
