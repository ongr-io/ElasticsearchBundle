# Working with results

> This chapter covers what comes from [find](find_functions.md) and [search](search.md) requests.

For all chapters below we will use a data example inserted in the elasticsearch content type:

```
// content type

{
  "id": 1,
  "title": "Dr. Damien Yundt DVM"
},
{
  "id": 2,
  "title": "Zane Heidenreich IV"
},
{
  "id": 3,
  "title": "Hattie Shields MD"
}

```

## Results iterator

Usually when any search action is performed the `DocumentIterator` will be returned. It has plenty of helper functions to aggregate more efficiently with the results.


Lets assume you search the index with:

```php

$repo = $this->get('es.manager.default.content');
$search = $repo->createSearch();
$termQuery = new MatchAllQuery();
$results = $repo->findDocuments($search);

```

So all 3 content elements will be found. `DocumentIterator`implements [`\Countable`](http://php.net/manual/en/class.countable.php), [`\Iterator`](http://php.net/manual/en/class.iterator.php) interfaces functions. The results are traversable and you can run `foreach` cycle through it.

```php

echo $results->count() . "\n";

/** @var AppBundle:Content $document */
foreach ($results as $document) {
    echo $document->title . "\n";
}

```

it will print:

```
3
Dr. Damien Yundt DVM
Zane Heidenreich IV
Hattie Shields MD
```

### Getting Document Score

In most cases Elasticsearch returns result score (`_score` field) for each document.
As this score might be different per search it's not treated as document field and
cannot be associated with document. You can get document's score from results iterator
while iterating:

```php
$results = $repository->findDocuments($search);

foreach ($results as $document) {
    echo $document->title, $results->getDocumentScore();
}
```

Example above prints titles of all documents following search score.

### Getting Document Sort

Similarly to Document score, during iteration you can get document sort, provided you
added a sort to your search, you can retrieve your sort value while iterating the
results:

```php
$results = $repository->execute($search);

foreach ($results as $document) {
    echo $document->title, $results->getDocumentSort();
}
```

#### Important notice

`DocumentIterator` doesn't cache or store generated document object. `Converter` directly returns the instance after it's requested and will generate again if it will be requested.

We highly recommend to `unset()` document instance after you don't need it or manage memory at your own way.

There is a possibility to change the `DocumentIterator` behaviour. Take a look at the [overwriting bundle parts](http://docs.ongr.io/ElasticsearchBundle/overwriting_bundle).

## Aggregations

If your search query includes aggregations you can reach calculated aggregations
by calling `getAggregation()` method.

In example below we show how to build query with aggregations and how to handle
aggregation results:

```php
$avgPriceAggregation = new AvgAggregation('avg_price');
$avgPriceAggregation->setField('price');

$brandTermAggregation = new TermsAggregation('brand');
$brandTermAggregation->setField('manufacturer');
$brandTermAggregation->addAggregation($avgPriceAggregation);

$query = new Search();
$query->addAggregation($brandTermAggregation);

$result = $this->get('es.manager.default.content')->findDocuments($query);

// Build a list of available choices
$choices = [];

foreach ($result->getAggregation('brand') as $bucket) {
    $choices[] = [
        'brand' => $bucket['key'],
        'count' => $bucket['doc_count'],
        'avg_price' => $bucket->find('avg_price')['value'],
    ];
}

var_export($choices);
```

The example above will print similar result:

```
array (
  0 => 
  array (
    'brand' => 'Terre Cortesi Moncaro',
    'count' => 7,
    'avg_price' => 20.42714282444545,
  ),
  1 => 
  array (
    'brand' => 'Casella Wines',
    'count' => 4,
    'avg_price' => 10.47249972820282,
  )
)
```
