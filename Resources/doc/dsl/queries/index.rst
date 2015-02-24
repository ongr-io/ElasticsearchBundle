Queries
=======

`Queries <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-queries.html>`_ should be used instead of filters:

- for full text search
- where the result depends on a relevance score

Currently documented are these query types:

- `has child query <index.html#id2>`_
- `has parent query <index.html#id3>`_

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