### Create simple query with Search DSL

````php
$manager = $this->get("es.manager");
$repository = $manager->getRepository('AcmeTestBundle:Customer');

$search = $repository->createSearch();
$matchAllQuery = new MatchAllQuery();
$search->addQuery($matchAllQuery);
$results = $repo->execute($search);
````

Fill form:
````json
{
  "query": {
    "match_all": {}
  }
}
````

In the results section, results will be returned `DocumentIterator` with loaded results. If user needs to get array directly, there are other options when is executed search:

````php
$results = $repo->execute($search, Repository::RESULTS_OBJECT); //default option to get DocumentIterator
$results = $repo->execute($search, Repository::RESULTS_ARRAY); // returns normalized array
$results = $repo->execute($search, Repository::RESULTS_RAW); // raw data what is got from elasticsearch
$results = $repo->execute($search, Repository::RESULTS_RAW_ITERATOR); // returns RawResultScanIterator
````

### Combining filters and queries
````php
$manager = $this->get("es.manager");
$repository = $manager->getRepository('AcmeTestBundle:Product');

$search = $repository->createSearch();

$queryStringQuery = new QueryStringQuery("description", "cherries");
$search->addQuery($matchAllQuery);

$termsQuery = new TermsQuery("wineColour", "Red");
$search->addQuery($termsQuery);

$rangeFilter = new RangeFilter('price', ['from' => 10, 'to' => 20]);
$search->addFilter($rangeFilter);

$results = $repo->execute($search);
````

It will create query:

````json
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
````