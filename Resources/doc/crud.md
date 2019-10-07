# CRUD actions

> To proceed with steps bellow it is necessary to read [mapping](mapping.md) topic and have defined documents in the bundle.

For all steps below we assume that there is an `AppBundle` with the `Content` document.

```php
// src/Document/Product.php

namespace App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * //alias and default parameters in the annotation are optional. 
 * @ES\Index(alias="content", default=true)
 */
class Content
{
    /**
     * @ES\Id()
     */
    public $id;

    /**
     * @ES\Property(type="text")
     */
    public $title;
}
```

> Important notice: you don't need your properties to be public, but they will
>need to have getters and setters otherwise.

## Manager

Elasticsearch bundle provides managers called indexes to able to handle several ES indexes 
for communication with elasticsearch.

Each index will have its dedicated manager that can be reached via service container by the 
name of the namespace of the Document class that represents the index in question. So in our
case: 

```php

$index = $container->get(Content::class);

```

> Important: The old implementation was to have manager and repositories dedicated
>for each document type. In 6.0 the functionalities of these services were merged
>into the index service for each document.


## Create a document

```php

$content = new Content();
$content->id = 5; // Optional, if not set, elasticsearch will set a random.
$content->title = 'Acme title';
$index->persist($content);
$index->commit();

```

## Update a document

```php

$content = $index->find(5); // Alternatively $index->findBy(['title' => 'acme title']);
$content->title = 'changed Acme title';
$index->persist($content);
$index->commit();

```

## Partial update

There is a quicker way to update a document field without creating object or fetching a whole document from elasticsearch. For this action we will use [partial update](https://www.elastic.co/guide/en/elasticsearch/guide/current/partial-updates.html) from elasticsearch.

To update a field you need to know the document `ID` and fields to update. Here's an example:

```php

$index = $this->get(Content::class);
$index->update(1, ['title' => 'new title']);

```

You can also update fields with script operation, lets say, you want to do some math:


```php

$index = $this->get(Content::class);
$index->update(1, [], 'ctx._source.stock+=1');

```
> Important: when using script update fields cannot be updated, leave empty array, otherwise you will get 400 exception.

`ctx._source` comes from groovy scripting and you have to enable it in elasticsearch config with: `script.groovy.sandbox.enabled: false`


In addition you also can get other document fields with the response of update, 
lets say we also want a content field and a new title, so just add them separated by a comma:


```php

$index = $this->get(Content::class);
$index = $repo->update(1, ['title' => 'new title'], null, ['fields' => 'title,content']);

```


## Delete a document

Document removal can be performed similarly to create or update action:

```php
$repo = $this->get('es.manager.default.content');
$content = $repo->remove(5);
```

