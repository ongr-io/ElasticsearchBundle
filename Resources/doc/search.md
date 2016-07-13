# How to perform a Search

## Structured search with DSL

If find functions are not enough, there is a possibility to perform a structured search using [query builder](https://github.com/ongr-io/ElasticsearchDSL). In a nutshell you can do any query or filter that is defined in [Elasticsearch Query DSL documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html).

To begin with structured search you will need a `Search` object.

e.g. We want to search for cities in Lithuania with more than 10K population

```php

$repo = $this->get('es.manager.default.city');
$search = $repo->createSearch();

$termQuery = new TermQuery('country', 'Lithuania');
$search->addQuery($termQuery);

$rangeQuery = new RangeQuery('population', ['from' => 10000]);
$search->addQuery($rangeQuery);

$results = $repo->execute($search);

```

> Important: fields `country` & `population` are the field names in elasticsearch type, NOT the document variables.

It will construct a query:

```json

{
    "query": {
        "bool": {
            "must": [
                {
                    "term": {
                        "country": "Lithuania"
                    }
                },
                {
                    "range": {
                        "population": {
                            "from": 10000
                        }
                    }
                }
            ]
        }
    }
}

```

> Important: by default result size in elasticsearch is 10, if you need more set size to your needs.

Setting size or offset is to the search is very easy, because it has getters and setters for these attributes. Therefore to set size all you need to do is to write

```php

$search->setSize(100);

```

Similarly other properties like Scroll, Timeout, MinScore and more can be defined.

For more query and filter examples take a look at the [Elasticsearch DSL library docs](https://github.com/ongr-io/ElasticsearchDSL/blob/master/docs/index.md). We covered all examples that we found in [Elasticsearch Query DSL documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html) how to cover in object oriented way.

> The results parsing is the same like in the find functions.

## Searching in Multiple Types

Previous section showed how to search in single type using repository. In some
cases you might need to search multiple types at once. In order to do that you
can use manager's `execute()` method (actually repository's `execute()` is just
a proxy for this method).

Lets say you have `City` and `State` documents with `title` field. Search all
cities and states with title "Indiana":

```php
$search = new Search();
$search->addQuery(new TermQuery('title', 'Indiana'));

$results = $manager->execute(
    // Array of documents representing different types
    ['AppBundle:City', 'AppBundle:State'], 
    $search
);
```

This example returns an iterator with all matching documents.

## Results count

Elasticsearch bundle provides support for [Count API](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-count.html). If you need only to count the results, this is a faster way to approach this. Here's an example of how to count cars by red color:

```php

$repo = $this->get('es.manager.default.cars');
$search = $repo->createSearch();

$termQuery = new TermQuery('color', 'red');
$search->addQuery($termQuery);

$count = $repo->count($search);

```

## Multi search

Multi search acts similarly to `bulk`, only instead of indexing, updating or deleting it deals with search. This means that it is possible to execute
a number of searches with a single request. This is an example of such a request:

```php

$manager->addSearch($search1);
$manager->addSearch($search2);
$manager->addSearch($search3);

$result = $manager->msearch();

```

The `$result` will be an array with the results from every search. Just as the `execute` method, `msearch` also excepts an optional parameter that
determins the form of the result. The default is `ONGR\ElasticsearchBundle\Result\Result::RESULTS_OBJECT`. It is also important that just as with
`bulk`, `msearch_size` can be defined in the configuration of the manager. This determines the maximum amount of searches that can be executed
with a single request. Once the number is reached the searches will be executed automatically, this means that `addSearch` method can return `null`
or if the `msearch_size` is reached, an array with the search results. The default value of this setting is 100.

It is also possible to provide headers for every search, if, for example, there is a need to execute a search on a single type in the index:

```php

$manager->addSearch($search, ['type' => 'my-type']);

```

In addition, there is a possibility to provide headers as parameters for `msearch` that will apply to the entire request:

```php

$manager->setMsearchParams(['search_type' => 'query_then_fetch']);

```