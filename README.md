# ONGR Elasticsearch Bundle

Elasticsearch Bundle was created in order to serve the need for
professional [Elasticsearch](https://www.elastic.co/products/elasticsearch) integration with enterprise level Symfony
applications. This bundle is:

* Supported by [ONGR.io](http://ongr.io) development team.
* Uses the official [elasticsearch-php](https://github.com/elastic/elasticsearch-php) client.
* Ensures full integration with Symfony framework.

Technical goodies:

* Provides nestable and DSL query builder to be executed by type repository services.
* Uses Doctrine-like document / entities document-object mapping using annotations.
* Query results iterators are provided for your convenience.
* Registers console commands for index and types management and data import / export.
* Designed in an extensible way for all your custom needs.

If you have any questions, don't hesitate to ask them on [![Join the chat at https://gitter.im/ongr-io/support](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/ongr-io/support)
 chat, or just come to say Hi ;).


[![Build Status](https://travis-ci.org/ongr-io/ElasticsearchBundle.svg?branch=master)](https://travis-ci.org/ongr-io/ElasticsearchBundle)
[![Coverage Status](https://coveralls.io/repos/ongr-io/ElasticsearchBundle/badge.svg?branch=master&service=github)](https://coveralls.io/github/ongr-io/ElasticsearchBundle?branch=master)
[![Latest Stable Version](https://poser.pugx.org/ongr/elasticsearch-bundle/v/stable)](https://packagist.org/packages/ongr/elasticsearch-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ongr-io/ElasticsearchBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ongr-io/ElasticsearchBundle/?branch=master)

## Documentation

The online documentation of the bundle is [here](Resources/doc/index.md)

For contribution to the documentation you can find it in the [contribute](Resources/doc/contribute.md) topic.


## Setup the bundle

#### Step 1: Install Elasticsearch bundle

Elasticsearch bundle is installed using [Composer](https://getcomposer.org).

```bash
php composer.phar require ongr/elasticsearch-bundle "~1.0"

```

> WARNING: "~1.0" stable is not released yet, we are in the final steps to finish everything and hope can release it soon. Here's the milestone what is left fr `1.0` https://github.com/ongr-io/ElasticsearchBundle/milestones/1.0.0

> "~1.0" indicates a version, chose any version that fits for you.

> Instructions for installing and deploying Elasticsearch can be found in [Elasticsearch installation page](https://www.elastic.co/downloads/elasticsearch).

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

#### Step 2: Add configuration

Add minimal configuration for Elasticsearch bundle.

```yaml

# app/config/config.yml

ongr_elasticsearch:
    connections:
        default:
            index_name: acme
    managers:
        default:
            connection: default
            mappings:
                - AppBundle

```

> This is the very basic example only, for more information, please take a look at the [configuration](Resources/doc/configuration.md) chapter.

In this particular example there is 2 things you should know. The index name in the connection node and the mappings. Mappings is the place where you documents are stored (more info at [the mapping chapter](Resources/doc/mapping.md)).


#### Step 3: Define your Elasticsearch types as `Document` objects

This bundle uses objects to represent Elasticsearch documents. Lets create a `Customer` class for customer document.

```php
// src/AppBundle/Document/Customer.php

namespace AppBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Document()
 */
class Customer
{
    /**
     * @var string
     *
     * @ES\Id()
     */
    public $id;

    /**
     * @var string
     *
     * @ES\Property(name="name", type="string")
     */
    public $name;
}

```

> This is the basic example only, for more information about mapping, please take a look at the [the mapping chapter](Resources/doc/mapping.md).


#### Step 4: Create index and mappings

Elasticsearch bundle provides several `CLI` commands. One of them is for creating index, run command in your terminal:

```bash

bin/console ongr:es:index:create

```

> More info about the rest of the commands can be found in the [commands chapter](Resources/doc/commands.md).


#### Step 5: Enjoy with the Elasticsearch

We advice to take a look at the [mapping chapter](Resources/doc/mapping.md) to configure the index. Search documentation for the Elasticsearch bundle is [available here](Resources/doc/search.md). And finally it's up to you what an amazing things you are gonna create :sunglasses:.


## License

This bundle is licensed under the MIT license. Please, see the complete license
in the bundle `LICENSE` file.
