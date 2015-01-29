Sorting
=======

Allows to add one or more sort on specific fields. We have three sorting types: `sort <index.html#sort-simple>`_, `script sorting <index.html#script-sorting>`_, `geo sorting <index.html#geo-sorting>`_.
Orders and modes are defined as constants in ``AbstractSort`` class.


Sort (simple)
-------------

Allow to sort by field values. Arguments explained:

- ``$field``` controls by which field to sort.
- ``$order`` controls sort order. Available options ``desc``, ``asc``.
- ``$nestedFilter`` controls sorting by nested field (`more <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-request-sort.html#_nested_sorting_example>`_).
- ``$mode`` controls what array value is picked for sorting the document it belongs to. Available options ``min``, ``max``, ``sum``, ``avg``.

.. code:: php

    ...

    $search = $repository->createSearch();
    $search->addSort(new Sort($field, $order, $nestedFilter, $mode));
    $results = $repo->execute($search);
    

Script sorting
--------------

Allow to sort based on custom scripts. Arguments explained:

- ``$script`` defines script to execute.
- ``$type`` defines type to be returned.
- ``$params`` associative array of custom params with values.
- ``$order`` controls sort order. Available options ``desc``, ``asc``.

.. code:: php

    ...

    $search = $repository->createSearch();
    $search->addSort(new ScriptSort($script, $type, $params, $order));
    $results = $repo->execute($search);
    
.. note:: more on script sorting can be found in elasticsearch `docs <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-request-sort.html#_script_based_sorting>`_.

Geo sorting
-----------

Allow to sort by geographical distance. Arguments explained:

- ``$field`` controls by which field to sort.
- ``$location`` location to sort from. Defined as array f.e. ``[-70, 40]``.
- ``$order`` controls sort order. Available options ``desc``, ``asc``.
- ``$unit`` controls units for measuring the distance.
- ``$mode`` controls what array value is picked for sorting the document it belongs to. Available options ``min``, ``max``, ``avg``.

.. code:: php

    ...
    
    $search = $repo->createSearch();
    $search->addSort(new GeoSort($field, $location, $order, $unit, $mode));
    $results = $repo->execute($search);


.. note:: More on geo sorting can be found `here <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-request-sort.html>`_.
