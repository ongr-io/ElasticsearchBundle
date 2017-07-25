# CRUD actions

> To proceed with steps bellow it is necessary to read [mapping](mapping.md) topic and have defined documents in the bundle.

For all steps below we assume that there is an `AppBundle` with the `Content` document.

```php
// src/AppBundle/Document/Content.php

namespace AppBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Document(type="content")
 */
class Content
{
    /**
     * @var string
     *
     * @ES\Id()
     */
    public $id;

    /**
     * @ES\Property(type="keyword")
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

In addition manager provides repository access, which enables direct access to the elasticsearch type.  
Repository represents a document. Whenever you need to do any action with a repository, you can access 
it like this:

```php

$manager = $this->get('es.manager');
$repo = $manager->getRepository('AppBundle:Content');

```

Alternatively:

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

## Update a document

```php

$content = $manager->find('AppBundle:Content', 5);
$content->title = 'changed Acme title';
$manager->persist($content);
$manager->commit();

```

## Partial update

There is a quicker way to update a document field without creating object or fetching a whole document from elasticsearch. For this action we will use [partial update](https://www.elastic.co/guide/en/elasticsearch/guide/current/partial-updates.html) from elasticsearch.

To update a field you need to know the document `ID` and fields to update. Here's an example:

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


In addition you also can get other document fields with the response of update, lets say we also want a content field and a new title, so just add them separated by a comma:


```php

$repo = $this->get('es.manager.default.content');
$response = $repo->update(1, ['title' => 'new title'], null, ['fields' => 'title,content']);

```


## Delete a document

Document removal can be performed similarly to create or update action:

```php
$manager->remove($content);
$manager->commit();
```

Alternatively you can remove document by ID (requires to have repository service):

```php

$repo = $this->get('es.manager.default.content');
$content = $repo->remove(5);

```
