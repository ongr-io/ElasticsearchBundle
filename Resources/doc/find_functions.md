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

> Important: with `$repo` you an initiate binding to more than one repository (elasticsearch type). `find()` function works only if there is one elasticsearch type loaded otherwise you will get logic exception.

e.g.

```php

$manager = $this->get('es.manager');
$repo = $manager->getRepository(['AppBundle:User', 'AppBundle:Content']);
$result = $repo->find(1); // Throws \LogicException

```

By default the response will be a `Document` object which is mapped to certain type you are searching. There is possible to change a result type to an array or raw response what is returned from elasticsearch.

```php

$repo = $this->get('es.manager.default.content');

/** @var $content Content **/
$content = $repo->find(1, Repository::RESULTS_ARRAY); // Default is Repository::RESULTS_OBJECT

```

The response will look like:

```
Array
(
    [title] => Quidem rem temporibus distinctio sunt repellat qui.
)
```

When using `Repository::RESULTS_RAW` the result will be:

```php

$repo = $this->get('es.manager.default.content');

/** @var $content Content **/
$content = $repo->find(5, Repository::RESULTS_RAW); // Default is Repository::RESULTS_OBJECT

```

```
Array
(
    [_index] => ongr
    [_type] => content
    [_id] => 1
    [_version] => 2
    [found] => 1
    [_source] => Array
        (
            [title] => Quidem rem temporibus distinctio sunt repellat qui.
        )

)
```

> The result type selection is also available in all other search methods.

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

By default the result will be `ResultIterator` objetct with loaded documents. There is also other options to change result type:

| Argument                   | Result                                                     |
|----------------------------|------------------------------------------------------------|
| Repository::RESULTS_RAW    | Returns raw output what comes from elasticsearch           |
| Repository::RESULTS_ARRAY  | An array of results with structure that matches a document |
| Repository::RESULTS_OBJECT | `ResultsIterator`                                          |

## Find one document by field

Completely the same as `findBy()` function, except it will return the first result. And if the result type will be `Repository::RESULTS_OBJECT` it wont return an iterator, instead `Document` object will be returned.

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
