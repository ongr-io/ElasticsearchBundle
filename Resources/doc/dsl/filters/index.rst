Filters
=======

`Filters <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-filters.html>`_ should be used instead of queries:

- for binary yes/no searches
- for queries on exact values

Currently documented are these filter types:

- `has child filter <index.html#id2>`_
- `has parent filter <index.html#id3>`_


has child filter
----------------
The `has_child <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-has-child-filter.html>`_ filter accepts a query and the child type to run against, and results in parent documents that have child docs matching the query.

.. note:: The has_child filter also accepts a query instead of a filter. The default type is filter.

Usage sample:

.. code:: php

    ...

    $hasChild = new HasChildFilter('comment', new TermFilter('name', 'foo'));
    $search->addFilter($hasChild);
    $search->addQuery(new MatchAllQuery());
    $results = $repo->execute($search);


The ``has_child`` filter with query:

.. code:: php

    ...

    $hasChild = new HasChildFilter('comment', new TermQuery('userName', 'foo'), [], HasChildFilter::INNER_QUERY);
    $search->addFilter($hasParent);
    $search->addQuery(new MatchAllQuery());
    $results = $repository->execute($search);


has parent filter
-----------------

The `has_parent <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-has-parent-filter.html>`_ filter accepts a query and a parent type. The query is executed in the parent document space, which is specified by the parent type. This filter returns child documents which associated parents have matched.

.. note:: The has_parent filter also accepts a query instead of a filter. The default type is filter.

Usage sample:

.. code:: php

    ...

    $hasParent = new HasParentFilter('content', new TermFilter('title', 'nested'));
    $search->addFilter($hasParent);
    $results = $repository->execute($search);


The ``has_parent`` filter with query:

.. code:: php

    ...

    $hasParent = new HasParentFilter('content', new TermQuery('title', 'nested'), [], HasParentFilter::INNER_QUERY);
    $search->addFilter($hasParent);
    $results = $repository->execute($search);