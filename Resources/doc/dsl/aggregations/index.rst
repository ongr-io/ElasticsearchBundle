Aggregations
============

`Aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations.html>`_ provide aggregated data based on a search query.
Simple aggregtions usage example:

.. code:: php

    ...
    
    $termsAggregation = new TermsAggregation('term_agg');
    $termsAggregation->setField('name');
    
    $search = $repository
        ->createSearch()
        ->addAggregation($termsAggregation);
        
    $result = $repository->execute($search);
    
    foreach ($result->getAggregations() as $name => $aggregation) {
        foreach($aggregation as $value) {
            // $value->getValue()
        }
    }
    
.. note:: Aggregations constructors takes string argument as aggregation name.

To nest aggregations it's fairly easy. Just add aggregation to another. f.e.

.. code:: php

    ...
    
    $range = new RangeAggregation('price_range_agg');
    $range->addRange(0, 16);
    
    $terms = new TermsAggregation('product_type_agg');
    $terms->setField('type');
    $terms->addAggregation($range);
    
    
Currently we support 9 different aggregation types:

- `Cardinality aggregation <index.html#id2>`_
- `Filter aggregation <index.html#id3>`_
- `Global aggregation <index.html#id4>`_
- `Nested aggregation <index.html#id5>`_
- `Range aggregation <index.html#id6>`_
- `Stats aggregation <index.html#id7>`_
- `Terms aggregation <index.html#id8>`_
- `TopHits aggregation <index.html#id9>`_
- `Children aggregation <index.html#id10>`_


Cardinality aggregation
-----------------------

`cardinality aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations-metrics-cardinality-aggregation.html>`_ single-value metrics aggregations that calculates an approximate count of distinct values.
Usage sample:

.. code:: php

    ...
    
    $aggregation = new CardinalityAggregation('price_cardi');
    $aggregation->setField('price');
    $aggregation->setPrecisionThreshold(100);
    $aggregation->setRehash(false);

    $search = $repo
        ->createSearch()
        ->addAggregation($aggregation);

    $results = $repo->execute($search);


Filter aggregation
------------------

`filter aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations-bucket-filter-aggregation.html>`_ filters aggregated documents. Often this will be used to narrow down the current aggregation context to a specific set of documents. Usage exmaple:

.. code:: php

    ...

    $aggregation = new FilterAggregation('title_filter');
    $filter = new RegexpFilter('title', 'pizza');
    $aggregation->setFilter($filter);

    $search = $repository
        ->createSearch()
        ->addAggregation($aggregation);
    
    $results = $repo->execute($search);

In this particular example we are filtering aggregations using regexp filter on field ``title`` with regexp ``pizza``.

Global aggregation
------------------

`global aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations-bucket-global-aggregation.html#search-aggregations-bucket-global-aggregation>`_ defines a single bucket of all the documents within the search execution context but it's **not** influenced by search query. Usage example:

.. code:: php

    $globalAggregation = new GlobalAggregation('global_agg');

    $rangeAggregation = new RangeAggregation('range_agg');
    $rangeAggregation->setField('price');
    $rangeAggregation->addRange(null, 40);

    $globalAggregation->addAggregation($rangeAggregation);

    $search = $repository
        ->createSearch()
        ->addAggregation($globalAggregation);

    $results = $repository->execute($search);

Nested aggregation
------------------

`nested aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations-bucket-nested-aggregation.html>`_
are special for aggregating nested document fields. Simple usage example:

.. code:: php

    ...
    
    $termAggregation = new TermsAggregation('sub_title_agg');
    $termAggregation->setField('sub_products.title');
    
    $nestedAggregation = new NestedAggregation('nested_agg');
    $nestedAggregation->setPath('sub_products');
    $nestedAggregation->addAggregation($termAggregation);

    $search = $repository
        ->createSearch()
        ->addAggregation($nestedAggregation);
    $results = $repository->execute($search);

Range aggregation
-----------------

`range aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations-bucket-range-aggregation.html>`_ extractes values from each document and aggregates against each given range. Usage exmaple:

.. code:: php

    ...
    
    $rangeAggregation = new RangeAggregation('price_agg');
    $rangeAggregation->addRange(null, 10);  // Less than 10.
    $rangeAggregation->addRange(10, 25);    // Between 10 and 25.
    $rangeAggregation->addRange(40, null);  // Greater than 40.
    
    $search = $repository
        ->createSearch()
        ->addAggregation($rangeAggregation);

    $result = $repository->execute($search);
    
Ranges can also be keyed, that means that you could fetch your range simply like this ``$result->getAggregations()['price_agg']['key']``.
By default that array is not associative.

How to make keyed ranges:

.. code:: php

    ...
    
    $rangeAggregation->setKeyed(true);
    $rangeAggregation->addRange(null, 10, 'cheap');
    
Now range less than 10 will have a key ``cheap``.

Stats aggregation
-----------------

`stats aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations-metrics-stats-aggregation.html>`_ calculates statistics over numeric values extracted from the aggregated documents. Statistics holds ``min``, ``max``, ``sum``, ``count``, ``avg`` values. Example:

.. code:: php

    ...

    $statsAggregation = new StatsAggregation('price_stats');
    $statsAggregation->setField('price');

    $search = $repository
        ->createSearch()
        ->addAggregation($statsAggregation);
    
    $results = $repository->execute($search);

Terms aggregation
-----------------

`terms aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations-bucket-terms-aggregation.html>`_  aggregates fields by unique values. Example:

.. code:: php

    ...
    
    $termAggregation = new TermsAggregation('name_agg');
    $termAggregation->setField('name');    

    $search = $repository
        ->createSearch()
        ->addAggregation($termAggregation);
    
    $results = $repository->execute($search);
    
It also contains some options that could be set.

+--------------------------+-----------------------------------------------------------------------------+---------------------------------------------------+
| Option                   | Description                                                                 | Method                                            |
+==========================+=============================================================================+===================================================+
| `Order`                  | Sets ordering . Available ``['_count' => 'desc']``, ``['_term' => 'asc']``. | setOrder($mode, $direction = self::DIRECTION_ASC) |
+--------------------------+-----------------------------------------------------------------------------+---------------------------------------------------+
| `Size`                   | Maximum buckets to return.                                                  | setSize($size)                                    |
+--------------------------+-----------------------------------------------------------------------------+---------------------------------------------------+
| `Minimum document count` | Minimum documents to consider                                               | setMinDocumentCount($count)                       |
+--------------------------+-----------------------------------------------------------------------------+---------------------------------------------------+
| `Include`                | Includes only values that match the pattern.                                | setInclude($include, $flags = '')                 |
+--------------------------+-----------------------------------------------------------------------------+---------------------------------------------------+
| `Exclude`                | Excludes values that match the pattern.                                     | setExclude($exclude, $flags = '')                 |
+--------------------------+-----------------------------------------------------------------------------+---------------------------------------------------+

Tophits aggregation
-------------------

`Tophits aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations-metrics-top-hits-aggregation.html>`_ This aggregator is intended to be used as a sub aggregator, so that the top matching documents can be aggregated per bucket. Usage:

.. code:: php

    ...
    
    $tophitsAggregation = new TopHitsAggregation('tophits_agg')
    $search = $repository
        ->createSearch()
        ->addAggregation($tophitsAggregation);
    
    $results = $repository->execute($search);

    foreach ($results->getAggregations()['tophits_agg'] as $tophit) {
        foreach ($tophit as $document) {
            // $document->getId();
        }
    }

It also accepts these options: ``size``, ``from``, ``sort``. All of them can be set through constructor or setters.

Children aggregation
--------------------

`children aggregations <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-aggregations-bucket-children-aggregation.html>`_ - A special single bucket aggregation that enables aggregating from buckets on parent document types to buckets on child documents.

.. code:: php

    ...

    $childrenAggregation = new ChildrenAggregation('test_children_agg');
    $childrenAggregation->setChildren('comment');

    $aggregation = new TermsAggregation('test_terms_agg');
    $aggregation->setField('comment.title');

    $childrenAggregation->addAggregation($aggregation);

    $search = $repository->createSearch()->addAggregation($childrenAggregation);
    $results = $repository->execute($search);