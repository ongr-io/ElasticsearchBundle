# How to perform a Search

## Structured search with DSL

If find functions are not enough, there is a possibility to perform a structured search using [query builder](https://github.com/ongr-io/ElasticsearchDSL). In a nutshell you can construct any queries, aggregations, etc. that are defined in [Elasticsearch Query DSL documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html).

To begin with structured search you will need a `Search` object. You need to add all the queries and other DSL constructs
to this search object and then perform the search from the repository service. There are three specialized `find` methods
dedicated for this task and you may choose between depending on your needs:

| method            | Return Type                                                                     |
|:-----------------:|:-------------------------------------------------------------------------------:|
| `findDocuments()` | Returns an instance of `DocumentIterator`                                       |
| `findArray()`     | Returns an instance of `ArrayIterator`                                          |
| `findRaw()`       | Returns an array of raw results with unaltered elasticsearch response structure | 

For the majority of cases, you will be using `findDocuments` that returns an iterator with hydrated documents. In addition
it provides a convenient way of handling aggregations, that can be accessed via `getAggregations()` method.

### Simple Example

In this example we will search for cities in Lithuania with more than 10K population

```php

$repo = $this->get('es.manager.default.city');
$search = $repo->createSearch();

$termQuery = new TermQuery('country', 'Lithuania');
$search->addQuery($termQuery);

$rangeQuery = new RangeQuery('population', ['from' => 10000]);
$search->addQuery($rangeQuery);

$results = $repo->findDocuments($search);

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

Setting size or offset is to the search is very easy, because it has getters and setters for these attributes. 
Therefore to set size all you need to do is to write

```php

$search->setSize(100);

```

Similarly other properties like Scroll, Timeout, MinScore and more can be defined.

For more query and filter examples take a look at the [Elasticsearch DSL library docs](https://github.com/ongr-io/ElasticsearchDSL/blob/master/docs/index.md). We covered all examples that we found in [Elasticsearch Query DSL documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html) how to cover in object oriented way.

## Searching in Multiple Types

Previous example illustrated the usual case scenario of performing a search in a single type.
However if you should ever find yourself in a rear situation where a search needs to be performed
on several types at once, you can use a specific `search` method in manager, that accepts an 
array with the names of types on which the search needs to be performed. 
 
However, unlike some of the `find` functions in repository this method returns only the raw results,
therefore the use of this method should be minimal and only when needed.

### Example of searching in multiple types

Lets say you have `City` and `State` documents with `title` field. Search all
cities and states with title "Indiana":

```php
$search = new Search();
$search->addQuery(new TermQuery('title', 'Indiana'));

$results = $manager->search(
    // Array of documents representing different types
    ['AppBundle:City', 'AppBundle:State'], 
    $search->toArray()
);
```

> Notice, that the second argument needs to be an array, not a `Search` instance.

## Results count

Elasticsearch bundle provides support for [Count API](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-count.html). If you need only to count the results, this is a faster way to approach this. Here's an example of how to count cars by red color:

```php

$repo = $this->get('es.manager.default.cars');
$search = $repo->createSearch();

$termQuery = new TermQuery('color', 'red');
$search->addQuery($termQuery);

$count = $repo->count($search);

```
