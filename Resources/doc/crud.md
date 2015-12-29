# CRUD actions

> To proceed with steps bellow it is necessary to read [mapping](mapping.md) topic and have defined documents in the bundle.

For all steps below we asume that there is an `AppBundle` with the `Content` document.

```php

<?php
//AppBundle:Content
namespace AppBundle/Document;

use ONGR\ElasticsearchBundle\Annotation as ES; // Alias to make short annotation.
use ONGR\ElasticsearchBundle\Document\DocumentTrait;

/**
 * @ES\Document(type="content")
 */
class Content
{
    use DocumentTrait;

    /**
     * @ES\Property(type="string", name="title")
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
$repo = $manager->getRepository('AppBundle:Content');

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

> id field comes from `DocumentTrait`. It's optional, in addition you can also use `ttl`, 'parent' and other special fields.

## Update a document

```php

$repo = $this->get('es.manager.default.content');
$content = $repo->find(5);
$content->title = 'changed Acme title';
$manager->persist($content);
$manager->commit();

```

## Partial update

There is a quicker way to update a document field without creating object or fetching a whole document from elasticsearch. For this action we will use [partial update](https://www.elastic.co/guide/en/elasticsearch/guide/current/partial-updates.html) from elasticsearch.

To update a field you need to know a document `ID` and fields to update. Here's an example:

```php

$repo = $this->get('es.manager.default.content');
$repo->update(1, ['title' => 'new title']);

```

You can also update fields with script operation, lets say, you want to do some math:


```php

$repo = $this->get('es.manager.default.product');
$repo->update(1, [], 'ctx._source.stock+=1');

```
> Important: when using script update fields cannot be updated, leave empty array, otherwise you will get 400 exception.

`ctx._source` comes from groovy scripting and you have to enable it in elasticsearch config with: `script.groovy.sandbox.enabled: false`


In addition you also can get other document fields with the response of update, lets say we also want a content field and a new title, so just add them with comma separated:


```php

$repo = $this->get('es.manager.default.content');
$response = $repo->update(1, ['title' => 'new title'], null, ['fields' => 'title,content']);

```


## Delete a document

```php

$repo = $this->get('es.manager.default.content');
$content = $repo->delete(5);

```
