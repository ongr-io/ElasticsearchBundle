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

> All `find` methods returns an object. If you want to get raw result use `execute()`.

## Find by field

Find by field uses [query_string query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html) to fetch a results by some field value.

> Document matches depends how mapping is defined. If you are not so sure that it will work as you expect better use structured search from `Search` object.


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
