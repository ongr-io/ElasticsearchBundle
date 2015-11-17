# Setup the bundle


### Step 1: Install Elasticsearch bundle

Elasticsearch bundle is installed using [Composer](https://getcomposer.org).

```

php composer.phar require ongr/elasticsearch-bundle "dev-master"

```

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


### Step 2: Add configuration

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


### Step 3: Define your Elasticsearch types as `Document` objects

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


### Step 4: Create index and mappings

Elasticsearch bundle provides several `CLI` commands. One of them is for creating index, run command in your terminal:

```bash

    app/console ongr:es:index:create

```

> More info about the rest of the commands can be found in the [commands chapter](commands.md).


### Step 5: Enjoy with the Elasticsearch

We advice to take a look at [mapping chapter](mapping.md) to configure the index. Search documentation for the Elasticsearch bundle is [available here](search.md). And finally it's up to you what an amazing things you are gonna create :sunglasses:.
