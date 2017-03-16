# Scan through the index

If the index is huge and in a single request there is not enough to get all records index scan might help.

> More info about index scanning in [elastic official docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html#scroll-scan)

You can scan index with any structured search and do a continuous scroll. To execute that kind of search you only need to append a scroll time amount to a `Search` object.

> Scan & Scroll doesn't work for `Repository::findArray()` method.

Here's an example with scrolling:

```php

$repo = $this->get('es.manager.default.city');
$search = $repo->createSearch();

$search->setScroll('10m'); // Scroll time

$termQuery = new TermQuery('country', 'Lithuania');
$search->addQuery($termQuery);

$results = $repo->findDocuments($search);

foreach ($results as $document) {

//....

}

```

Usually result amount will be 10 (if no size set), but if the result type is any iterator, it will do a next request when the results set limit is reached and will continue results scrolling in foreach cycle. You dont have to worry about any scroll ids and how to perform second request, everything is handled automatically.

If you are using `findRaw()` method, then bundle won't request next iteration and you should do it by yourself. Here's an example how to do it:


```php

$repo = $this->get('es.manager.default.city');
$search = $repo->createSearch();

$search->setScroll('10m'); // Scroll time

$termQuery = new TermQuery('country', 'Lithuania');
$search->addQuery($termQuery);

$results = $repo->findRaw($search);

foreach ($results as $raw) {

// Do something with RAW results

}

$nextIteration = $repo->getManager()->scroll($results['_scroll_id'], '10m');

```
