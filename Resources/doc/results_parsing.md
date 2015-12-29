# Working with results

> This chapter covers what comes from [find](find_functions.md) and [search](search.md) requests.

For all chapters below we will us a data example inserted in the elasticsearch content type:

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

Whenever any search actions is performed and `Result::RESULTS_OBJECT` is selected as the result type the `DocumentIterator` will be returned. It has plenty of helper functions to aggregate more efficiently with the results.


Lets assume you search the index with:

```php

$repo = $this->get('es.manager.default.content');
$search = $repo->createSearch();
$termQuery = new MatchAllQuery();
$results = $repo->execute($search, Result::RESULTS_OBJECT); // Result::RESULTS_OBJECT is the default value

```

So all 3 content elements will be found. `DocumentIterator`implements [`\Countable`](http://php.net/manual/en/class.countable.php), [`\Iterator`](http://php.net/manual/en/class.iterator.php) interfaces functions. The results are traversable and you can run `foreach` cycle through it.

```php

echo $results->count() . '\n';

/** @var AppBundle:Content $document */
foreach ($results as $document) {
    echo $document->title . '\n';
}

```

it will print:

```
3
Dr. Damien Yundt DVM
Zane Heidenreich IV
Hattie Shields MD
```

#### Important notice

`DocumentIterator` doesn't cache or store generated document object. `Converter` directly returns the instance after it's requested and will generate again if it will be requested.

We highly recommend to `unset()` document instance after you dont need it or manage memory at your own way.

There is possible to change the `DocumentIterator` behaviour. Take a look at the [overwriting bundle parts](overwriting_bundle.md).
