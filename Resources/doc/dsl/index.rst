Searching via DSL
=================

Create simple query with Search DSL
-----------------------------------

.. code:: php

    $manager = $this->get("es.manager");
    $repository = $manager->getRepository('AcmeTestBundle:Customer');

    $search = $repository->createSearch();
    $matchAllQuery = new MatchAllQuery();
    $search->addQuery($matchAllQuery);
    $results = $repository->execute($search);

Fill form:

.. code:: json

    {
      "query": {
        "match_all": {}
      }
    }

In the results section, results will be returned ``DocumentIterator`` with loaded results. If user needs to get array directly, there are other options when is executed search:

.. code:: php

    $results = $repository->execute($search, Repository::RESULTS_OBJECT);       // Default option to get DocumentIterator.
    $results = $repository->execute($search, Repository::RESULTS_ARRAY);        // Returns normalized array.
    $results = $repository->execute($search, Repository::RESULTS_RAW);          // Raw data what is got from elasticsearch.
    $results = $repository->execute($search, Repository::RESULTS_RAW_ITERATOR); // Returns RawResultScanIterator.

Combining filters and queries
-----------------------------

.. code:: php

    $manager = $this->get("es.manager");
    $repository = $manager->getRepository('AcmeTestBundle:Product');

    $search = $repository->createSearch();

    $queryStringQuery = new QueryStringQuery("cherries", ["default_field"=>"description"]);
    $search->addQuery($queryStringQuery);

    $termsQuery = new TermsQuery("wineColour", ["Red"]);
    $search->addQuery($termsQuery);

    $rangeFilter = new RangeFilter('price', ['from' => 10, 'to' => 20]);
    $search->addFilter($rangeFilter);

    $results = $repository->execute($search);

It will create query:

.. code:: json

    {
      "fields": [
        "title",
        "description",
        "price",
      ],
      "query": {
        "bool": {
          "must": [
            {
              "query_string": {
                "default_field": "description",
                "query": "cherries"
              }
            },
            {
              "terms": {
                "wineColour": [
                  "Red"
                ]
              }
            },
            {
              "filtered": {
                "filter": {
                  "range": {
                    "price": {
                      "from": 10,
                      "to": 20
                    }
                  }
                }
              }
            }
          ]
        }
      }
    }

Available API's
---------------

.. toctree::
            :maxdepth: 10
            :titlesonly:

            queries/index
            filters/index
            sorting/index
            aggregations/index
            highlights/index
            suggesters/index
