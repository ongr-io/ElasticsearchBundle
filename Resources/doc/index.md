---
title: Introduction
type: elasticsearch-bundle
order: 1
---

> This documentation is for **`6.x`** version. If you look for **`5.x`** or earlier take a look at the [Github Resources folder](https://github.com/ongr-io/ElasticsearchBundle/tree/5.2/Resources/doc).

## Reference links

Welcome to the ElasticsearchBundle, the modern solution to work with [Elasticsearch database](https://www.elastic.co/products/elasticsearch) in the [Symfony](https://github.com/symfony/symfony-standard) applications. We created this bundle with love :heart: and we think you will love it too.

* [Mapping explained](mapping.md)
* [Using Meta-Fields](meta_fields.md)
* [Configuration](configuration.md)
* [Console commands](commands.md)
* [How to do a simple CRUD actions](crud.md)
* [Quick find functions](find_functions.md)
* [How to search the index](search.md)
* [Scan through the index](scan.md)
* [Parsing the results](results_parsing.md)

## How to install

#### Step 1: Install Elasticsearch bundle

Elasticsearch bundle is installed using [Composer](https://getcomposer.org).

```bash
composer require ongr/elasticsearch-bundle "~6.0"
```

> Instructions for installing and deploying Elasticsearch can be found in
 [Elasticsearch installation page][17].

Enable Elasticsearch bundle in your AppKernel:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = [
        // ...
        new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
    ];
    
    // ...
}

```

#### Step 2: (OPTIONAL) Add configuration

> Since bundle v6 the configuration is not necessary. Everything can be set through annotation. 

```yaml
# app/config/config.yml
ongr_elasticsearch:
    indexes:
        App\Document\Product:
            alias: product
            hosts:
                - 127.0.0.1:9200
```

The configuration might be handy if you want to set an index alias name or other parameter from `.env` or ENV. 


#### Step 3: Define your Elasticsearch types as `Document` objects

This bundle uses objects to represent Elasticsearch documents. Lets create a `Product` class to represent products.

```php
// src/Document/Product.php

namespace App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Index(alias="my_product")
 */
class Product
{
    /**
     * @ES\Id()
     */
    public $id;

    /**
     * @ES\Property(type="text")
     */
    public $title;

    /**
     * @ES\Property(type="float")
     */
    public $price;
}

```

#### Step 4: Create index and mappings

Elasticsearch bundle provides several `CLI` commands. One of them is for creating index, run command in your terminal:

```bash

bin/console ongr:es:index:create

```

> More info about the rest of the commands can be found in the [commands chapter][10].


#### Step 5: Enjoy the ElasticsearchBundle

Enjoy :rocket: