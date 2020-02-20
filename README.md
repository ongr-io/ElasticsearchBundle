# ONGR Elasticsearch Bundle

[![Build Status](https://travis-ci.org/ongr-io/ElasticsearchBundle.svg?branch=master)](https://travis-ci.org/ongr-io/ElasticsearchBundle)
[![Latest Stable Version](https://poser.pugx.org/ongr/elasticsearch-bundle/v/stable)](https://packagist.org/packages/ongr/elasticsearch-bundle)
[![codecov](https://codecov.io/gh/ongr-io/ElasticsearchBundle/branch/master/graph/badge.svg)](https://codecov.io/gh/ongr-io/ElasticsearchBundle)
[![Total Downloads](https://poser.pugx.org/ongr/elasticsearch-bundle/downloads)](https://packagist.org/packages/ongr/elasticsearch-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ongr-io/ElasticsearchBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ongr-io/ElasticsearchBundle/?branch=master)


Elasticsearch Bundle was created in order to serve the need for
professional [Elasticsearch][1] integration with enterprise level Symfony
applications. This bundle is:

* Uses the official [elasticsearch-php][2] client.
* Ensures full integration with Symfony framework and Symfony Flex.

Technical goodies:

* Provides a DSL query builder which represent all ElasticSearch endpoints in the objective way.
* Provides interactive Document object generator via CLI command (`ongr:es:document:generate`)
* Creates a familiar Doctrine-like way to work with documents(entities) document-object mapping using annotations.
* Several query results iterators are provided for your convenience to work with results.
* Console CLI commands for the index management and data import/export/reindex.
* Profiler that integrates in the Symfony debug bar and shows all executed queries.
* Designed in an extensible way for all your custom needs.
* Supports Symfony FLEX.

If you need any help, [stack overflow][3] is the preferred way to get answers.
is the preferred and recommended way to ask questions about ONGR bundles and libraries.

If you like this library, help me to develop it by buying a cup of coffee

<a href="https://www.buymeacoffee.com/zIKBXRc" target="_blank"><img src="https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png" alt="Buy Me A Coffee" style="height: 41px !important;width: 174px !important;box-shadow: 0px 3px 2px 0px rgba(190, 190, 190, 0.5) !important;-webkit-box-shadow: 0px 3px 2px 0px rgba(190, 190, 190, 0.5) !important;" ></a>


## Version matrix

| Elasticsearch version | ElasticsearchBundle version      |
| --------------------- | -------------------------------- |
| >= 7.0                | ~7.x                             |
| >= 6.0, < 7.0         | ~6.x                             |
| >= 5.0, < 5.0         | ~5.x, ~6.x (indexes with 1 type) |
| >= 2.0, < 5.0         | >=1.0, < 5.0                     |
| >= 1.0, < 2.0         | >= 0.10, < 1.0                   |
| <= 0.90.x             | < 0.10                           |

## Documentation

The online documentation of the bundle can be found in [http://docs.ongr.io][4].
Docs source is stored within the repo under `Resources/doc/`, so if you see a typo or some inaccuracy, please submit a PR or at least an issue to fix it!

*For contribution to the documentation you can find it in the [contribute][5] topic.*

## FAQ
* [Index mapping][6]
* [Configuration][7]
* [Console commands][8]
* [How to do simple CRUD actions][9]
* [Quick find functions][10]
* [How to execute search in the index][11]
* [Parsing the results][12]

## Setup the bundle

#### Step 1: Install Elasticsearch bundle

Elasticsearch bundle is installed using [Composer][13].

```bash
php composer.phar require ongr/elasticsearch-bundle "~6.0"
```

> Instructions for installing and deploying Elasticsearch can be found in
 [Elasticsearch installation page][14].

Enable ElasticSearch bundle in your AppKernel:

```php
<?php
// config/bundles.php

return [
    // ...
    ONGR\ElasticsearchBundle\ONGRElasticsearchBundle::class => ['all' => true],
];

```

#### (OPTIONAL) Step 2: Add configuration 

Add minimal configuration for Elasticsearch bundle.

```yaml

# config/packages/ongr_elasticsearch.yaml
ongr_elasticsearch:
    analysis:
        filter:
            edge_ngram_filter: #-> your custom filter name to use in the analyzer below
                type: edge_ngram 
                min_gram: 1
                max_gram: 20
        analyzer:
            eNgramAnalyzer: #-> analyzer name to use in the document field
                type: custom
                tokenizer: standard
                filter:
                    - lowercase
                    - edge_ngram_filter #that's the filter defined earlier
    indexes:
        App\Document\Product:
            hosts: [elasticsearch:9200] # optional, the default is 127.0.0.1:9200

```

> This is the very basic example only, for more information, please take a look at the [configuration][9] chapter.

#### Step 3: Define your Elasticsearch types as `Document` objects

This bundle uses objects to represent Elasticsearch documents. Lets create the `Product` class for the `products` index.

```php
// src/Document/Product.php

namespace App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * //alias and default parameters in the annotation are optional. 
 * @ES\Index(alias="products", default=true)
 */
class Product
{
    /**
     * @ES\Id()
     */
    public $id;

    /**
     * @ES\Property(type="text", analyzer="eNgramAnalyzer")
     */
    public $title;

    /**
     * @ES\Property(type="float")
     */
    public $price;
}

```

> This is the basic example only, for more information about a mapping, please take a look
 at the [the mapping chapter][6].


#### Step 4: Create index and mappings

Elasticsearch bundle provides several `CLI` commands. One of them is for creating an index, run the command in your terminal:

```bash

bin/console ongr:es:index:create

```
Now the `products` index should be created with fields from your document.  

> More info about the rest of the commands can be found in the [commands chapter][8].


#### Step 5: Enjoy with the Elasticsearch

Full documentation for the Elasticsearch bundle is [available here][4].
I hope you will create amazing things with it :sunglasses: .

> Please note that the updating process of the documentation of the bundle to 6.0
>is still under way. Read the [configuration][7] and [crud][9] sections that are 
>already updated and will allow you to have the basic functions of the bundle. We
>will update the rest of the documentation as soon as possible

## Troubleshooting
* [How to upgrade from the older versions?][15]
* [How to overwrite some parts of the bundle?][16]

## License

This bundle is licensed under the [MIT license](LICENSE). Please, see the complete license
in the bundle `LICENSE` file.

[1]: https://www.elastic.co/products/elasticsearch
[2]: https://github.com/elastic/elasticsearch-php
[3]: http://stackoverflow.com/questions/tagged/ongr
[4]: http://docs.ongr.io/ElasticsearchBundle
[5]: http://docs.ongr.io/common/Contributing
[6]: http://docs.ongr.io/ElasticsearchBundle/mapping
[7]: http://docs.ongr.io/ElasticsearchBundle/configuration
[8]: http://docs.ongr.io/ElasticsearchBundle/commands
[9]: http://docs.ongr.io/ElasticsearchBundle/crud
[10]: http://docs.ongr.io/ElasticsearchBundle/find_functions
[11]: http://docs.ongr.io/ElasticsearchBundle/search
[12]: http://docs.ongr.io/ElasticsearchBundle/results_parsing
[13]: https://getcomposer.org
[14]: https://www.elastic.co/downloads/elasticsearch
[15]: http://docs.ongr.io/ElasticsearchBundle/upgrade
[16]: http://docs.ongr.io/ElasticsearchBundle/overwriting_bundle

