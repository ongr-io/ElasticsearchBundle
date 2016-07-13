# Quick find functions

> If you haven't read about maping and simple usage of the bundle please take a look at first to the [mapping](mapping.md) docs.

For all examples below we will use `Content` document class from the [CRUD actions](crud.md) chapter.

## Find a document by ID

Find by id will execute [elasticsearch get query](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-get.html).

```php

$repo = $this->get('es.manager.default.content');

/** @var $content Content **/
$content = $repo->find(1); // 5 is the document _uid in the elasticsearch.

```

> All `find` methods return an object. If you want to get raw result use `execute($search, Result::RESULTS_RAW)`.

## Find multiple documents by ID

If multiple documents need to be found by their IDs, `findByIds()` method can be used. It accepts an array of document IDs
and returns `DocumentIterator` with found documents:

```php

$documents = $repo->findByIds(['26', '8', '11']);

```

For this functionality the `Repository` uses
[elasticsearch multi get API](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-multi-get.html).

## Find by field

Find by field uses [query_string query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html) to fetch results by a specified field value.

> Document matches heavily depend on how mapping is defined. If you are unsure of whether it will work, it is better to use structured search from `Search` object.


```php

$repo = $this->get('es.manager.default.content');

/** @var $content Content **/
$content = $repo->findBy(['title' => 'Acme']);

```

The return will be:

```
Array
(
  [0] => Array
  (
      [title] => Acme
  )
)
```

Also with `findBy` you can define the way the results are ordered, limit the amount of retrieved documents and define the offset of the results, eg:

```php

$content = $repo->findBy(['title' => 'Acme'], ['price' => 'asc'], 20, 10);

```

This will return up to 20 documents with the word 'Acme' in their title, also it will skip the first 10 results and the results will be ordered from the ones with the smallest price to the ones with the highest.

## Find one document by field

Completely the same as `findBy()` function, except it will return the first document.

```php

$repo = $this->get('es.manager.default.content');

/** @var $content Content **/
$content = $repo->findOneBy(['title' => 'Acme']);

```

The return will be:

```
Array
(
  [title] => Acme
)
```
