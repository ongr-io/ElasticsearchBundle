# ONGR Elasticsearch Bundle

Elasticsearch Bundle was created in order to serve the need for
professional [elasticsearch](https://www.elastic.co/products/elasticsearch) integration with enterprise level Symfony
2 systems. This bundle is:

* Supported by [ONGR.io](http://ongr.io) development team.
* Uses the official [elasticsearch-php](https://github.com/elasticsearch/elasticsearch-php>) client.
* Ensures full integration with Symfony 2 framework.

Technical goodies:

* Provides nestable and DSL query builder to be executed by type repository services.
* Uses Doctrine-like document / entities document-object mapping using annotations.
* Query results iterators are provided for your convenience.
* Registers console commands for index and types management and data import / export.
* Designed in an extensible way for all your custom needs.

If you have any questions, don't hesitate to ask them on [![Join the chat at https://gitter.im/ongr-io/support](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/ongr-io/support?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
 chat, or just come to say Hi ;).


[![Build Status](https://travis-ci.org/ongr-io/ElasticsearchBundle.svg?branch=master)](https://travis-ci.org/ongr-io/ElasticsearchBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ongr-io/ElasticsearchBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ongr-io/ElasticsearchBundle/?branch=master)
[![Code Claimate](https://codeclimate.com/github/ongr-io/ElasticsearchBundle/badges/gpa.svg)](https://codeclimate.com/github/ongr-io/ElasticsearchBundle)
[![Coverage Status](https://coveralls.io/repos/ongr-io/ElasticsearchBundle/badge.svg?branch=master&service=github)](https://coveralls.io/github/ongr-io/ElasticsearchBundle?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/3ebe6515-f946-49a3-8d9c-e5fc8d6ce5c2/mini.png)](https://insight.sensiolabs.com/projects/3ebe6515-f946-49a3-8d9c-e5fc8d6ce5c2)
[![Codacy Badge](https://api.codacy.com/project/badge/48f72253ed904482baca2f19d0dcde00)](https://www.codacy.com/app/ongr/ElasticsearchBundle)
[![Latest Stable Version](https://poser.pugx.org/ongr/elasticsearch-bundle/v/stable)](https://packagist.org/packages/ongr/elasticsearch-bundle)
[![Total Downloads](https://poser.pugx.org/ongr/elasticsearch-bundle/downloads)](https://packagist.org/packages/ongr/elasticsearch-bundle)
[![Latest Unstable Version](https://poser.pugx.org/ongr/elasticsearch-bundle/v/unstable)](https://packagist.org/packages/ongr/elasticsearch-bundle)
[![License](https://poser.pugx.org/ongr/elasticsearch-bundle/license)](https://packagist.org/packages/ongr/elasticsearch-bundle)


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

<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
    );
}

```

Yep, that's it, **1 step** installation. All the next steps are **optional**. Of course, you most likely will need to add some customizations, like create document mapping, few managers and etc. So look below how easy is to continue.


#### Step 2: Add configuration

Add minimal configuration for Elasticsearch bundle.

```yaml

#app/config/config.yml
ongr_elasticsearch:
    connections:
        default:
            index_name: acme
    managers:
        default:
            connection: default
            mappings:
                - AcmeDemoBundle

```

> This is the very basic example only, for more information, please take a look at the [configuration](configuration.md) chapter.

In this particular example there is 2 things you should know. The index name in the connection node and the mappings. Mappings is the place where you documents are stored (more info at [the mapping chapter](mapping.md)).


#### Step 3: Define your Elasticsearch types as `Document` objects

Elasticsearch bundle uses ``Document`` objects to communicate with elasticsearch objects. Now lets create a ``Customer`` class in the ``Document`` folder. We assume that we have an AcmeDemoBundle installed.

> Folder name could not be changed, please make sure you put your documents in the righ place.

```php

<?php
namespace Acme\DemoBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\AbstractDocument;

/**
 * @ES\Document
 */
class Customer extends AbstractDocument
{
    /**
     * @var string
     *
     * @ES\Property(name="name", type="string")
     */
    private $name;

    // Setters and getters boilerplate follows:
    // ...
}

```

> This is the basic example only, for more information about mapping, please take a look at the [the mapping chapter](mapping.md).


#### Step 4: Create index and mappings

Elasticsearch bundle provides several `CLI` commands. One of them is for creating index, run command in your terminal:

```bash

    app/console ongr:es:index:create

```

> More info about the rest of the commands can be found in the [commands chapter](commands.md).


#### Step 5: Enjoy with the Elasticsearch

We advice to take a look at the [mapping chapter](mapping.md) to configure the index. Search documentation for the Elasticsearch bundle is [available here](search.md). And finally it's up to you what an amazing things you are gonna create :sunglasses:.


## License

This bundle is under the MIT license. Please, see the complete license
in the bundle ``LICENSE`` file.
