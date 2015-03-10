Queries
=======

`Queries <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-queries.html>`_ should be used instead of filters:

- for full text search
- where the result depends on a relevance score

Currently documented are these query types:

- `boosting query <index.html#id2>`_
- `has child query <index.html#id3>`_
- `has parent query <index.html#id4>`_
- `dis max query <index.html#5>`_
- `prefix query <index.html#6>`_

boosting query
--------------

The `boosting <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-boosting-query.html>`_ query can be used to effectively demote results that match a given query.
Query structure: (positive term(s), negative term(s), negative_boost). All fields are required.
Usage sample:

.. code:: php

    ...

    $boosting = new BoostingQuery(new TermQuery('title', 'foo'),  new TermQuery('title', 'bar'), 0.3);
    $search = $repo
        ->createSearch()
        ->addQuery($boosting);
    $results = $repo->execute($search);


has child query
---------------
The `has_child <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-has-child-query.html>`_ query works the same as the has_child filter, by automatically wrapping the filter with a constant_score (when using the default score type).

Usage sample:

.. code:: php

    ...

    $hasChild = new HasChildQuery('comment', new TermQuery('title', 'bar'));
    $search = $repo
        ->createSearch()
        ->addQuery($hasChild);
    $results = $repo->execute($search);


has parent query
----------------
The `has_parent <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-has-parent-query.html>`_ query works the same as the has_parent filter, by automatically wrapping the filter with a constant_score (when using the default score type)

Usage sample:

.. code:: php

    ...

    $hasParent = new HasParentQuery('content', new TermQuery('name', 'foo'));
    $search = $repo
        ->createSearch()
        ->addQuery($hasParent);
    $results = $repo->execute($search);


dis max query
-------------
The `dis max <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-dis-max-query.html>`_ query that generates the union of documents produced by its subqueries, and that scores each document with the maximum score for that document as produced by any subquery, plus a tie breaking increment for any additional matching subqueries.

Usage sample:

.. code:: php

    ...

    $disMax = new DisMaxQuery([new TermQuery('title', 'foo'), new TermQuery('name', 'bar')]);
    $search = $repo
        ->createSearch()
        ->addQuery($disMax);
    $results = $repo->execute($search);


prefix query
------------
The `prefix <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-prefix-query.html>`_ query matches documents that have fields containing terms with a specified prefix (not analyzed).

Usage sample:

.. code:: php

    ...

    $prefix = new PrefixQuery('title', 'f');
    $search = $repo
        ->createSearch()
        ->addQuery($prefix);
