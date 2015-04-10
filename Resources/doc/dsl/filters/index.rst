Filters
=======

`Filters <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-filters.html>`_ should be used instead of queries:

- for binary yes/no searches
- for queries on exact values

Currently documented are these filter types:

- `Has Child Filter <index.html#id2>`_
- `Has Parent Filter <index.html#id3>`_
- `Geo Bounding Box Filter <index.html#id4>`_
- `Geo Distance Filter <index.html#id5>`_
- `Geo Distance Range Filter <index.html#id6>`_
- `Geo Polygon Filter <index.html#id7>`_
- `GeoShape Filter <index.html#id8>`_
- `Geohash Cell Filter <index.html#id9>`_


Has Child Filter
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

    $hasChild = new HasChildFilter('comment', new TermQuery('userName', 'foo'));
    $hasChild->setDslType('query');
    $search->addFilter($hasChild);
    $search->addQuery(new MatchAllQuery());
    $results = $repository->execute($search);


Has Parent Filter
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

    $hasParent = new HasParentFilter('content', new TermQuery('title', 'nested'), []);
    $hasParent->setDslType('query');
    $search->addFilter($hasParent);
    $results = $repository->execute($search);


Geo Bounding Box Filter
-----------------------

The `geo bounding box <http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-bounding-box-filter.html>`_ filters documents that include only hits that exists in a specified bounding box.

.. note:: The filter requires the geo_point type to be set on the relevant field.

Usage sample:

.. code:: php

    ...

    $geoBoundingBoxFilter = new GeoBoundingBoxFilter('location', [
        ['lat' => 40.73, 'lon' => -74.1],
        ['lat' => 40.01, 'lon' => -71.12],
    ]);
    $search->addFilter($geoBoundingBoxFilter);
    $results = $repository->execute($search);



Geo Distance Filter
-------------------

The `geo distance <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-geo-distance-filter.html>`_ filters documents that include only hits that exists within a specific distance from a geo point.

.. note:: The filter requires the geo_point type to be set on the relevant field.

Usage sample:

.. code:: php

    ...

    $geoDistanceFilter = new GeoDistanceFilter('location', '200km', ['lat' => 40, 'lon' => -70]);
    $search->addFilter($geoDistanceFilter);
    $results = $repository->execute($search);


Geo Distance Range Filter
-------------------------

The `geo distance range <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-geo-distance-range-filter.html>`_ filters documents that exists within a range from a specific point.

.. note:: The filter requires the geo_point type to be set on the relevant field.

Usage sample:

.. code:: php

    ...

    $geoDistanceRangeFilter = new GeoDistanceRangeFilter('location', ['from' => '200km', 'to'=>'400km'], ['lat' => 40, 'lon' => -70]);
    $search->addFilter($geoDistanceRangeFilter);
    $results = $repository->execute($search);


Geo Polygon Filter
------------------

The `geo polygon <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-geo-polygon-filter.html>`_ filter allows to include hits that only fall within a polygon of points.

.. note:: The filter requires the geo_point type to be set on the relevant field.

Usage sample:

.. code:: php

    ...

    $geoPolygonFilter = new GeoPolygonFilter('location', [
        ['lat' => 20, 'lon' => -80],
        ['lat' => 30, 'lon' => -40],
        ['lat' => 70, 'lon' => -90],
    ]);
    $search->addFilter($geoPolygonFilter);
    $results = $repository->execute($search);


GeoShape Filter
---------------

The `geoshape <http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-shape-filter.html>`_ filters documents indexed using the *geo_shape* type.

.. note:: The filter requires the geo_point type to be set on the relevant field.

Usage sample:

.. code:: php

    ...

    $geoShapeFilter = new GeoShapeFilter();
    $geoShapeFilter->addShape('location', 'Point', [13.400544, 52.530286]);
    $search->addFilter($geoShapeFilter);
    $results = $repository->execute($search);


Geohash Cell Filter
-------------------

The `geohash cell <http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geohash-cell-filter.html>`_ filter provides access to a hierarchy of geohashes. By defining a geohash cell, only geopoints within this cell will match this filter.

.. note:: The filter requires the geo_point type to be set on the relevant field.

Usage sample:

.. code:: php

    ...

    $geohashCellFilter = new GeohashCellFilter('pin', ['lat' => 13.4080, 'lon' => 52.5186]);
    $search->addFilter($geohashCellFilter);
    $results = $repository->execute($search);
