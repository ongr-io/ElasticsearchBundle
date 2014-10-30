Step 1: Install Elasticsearch bundle
---------------------------

Elasticsearch bundle is installed using [Composer](https://getcomposer.org).

```bash
$ php composer.phar require ongr/elasticsearch-bundle "~0.1"
```

> ### Elasticsearch
>
> Instructions for installing and deploying Elasticsearch can be found
> [here](http://www.elasticsearch.org/guide/reference/setup/installation/).


Step 2: Enable Elasticsearch bundle
---------------------------

Enable Elasticsearch bundle in your AppKernel:

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new ONGR\ElasticsearchBundle\ONGR\ElasticsearchBundle(),
    );
}
```

Step 3: Add configuration
-----------------------------

Add minimal configuration for Elasticsearch bundle. 


```yaml
#app/config/config.yml
ongr_elasticsearch:
    connections:
        default:
            hosts:
                - { host: 127.0.0.1:9200 }
                - { host: 10.0.0.1:9200 }
            index_name: acme
            settings:
                refresh_interval: -1
                number_of_replicas: 1
        customer:
            hosts:
                - { host: 127.0.0.1:9200 }
            index_name: store
            settings:
                refresh_interval: -1
                number_of_replicas: 2
    managers:
        default:
            connection: default
            mappings:
                - AcmeDemoBundle
        customer:
            connection: customer
            mappings:
                - AcmeStoreBundle
```

> This is the basic example only, for more information about mapping, please take a look at [Mapping](mapping.md) chapter.

In this particular example, we defined connections and managers, as you noticed it could be combined together. 

Every connection handles Elasticsearch index and contains its client. In the configuration tree connections array defines hosts, index name and settings.

Node `managers` configures document managers that are responsible for working with types (executing queries, persisting documents, etc). Managers can be accessed through service tag `es.manager` (alias of `es.manager.default`), `es.manager.customer` (as of customer manager).


Step 4: Define your types as `Documents`
-----------------------------

Elasticsearch bundle to communicate with elasticsearch uses `Document` objects. Now create a `Customer` class in the `Document` folder. 

> Folder name could be changed in config settings

````php
<?php
namespace Acme\AcmeDemoBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Document\DocumentTrait;

/**
 * @ES\Document
 */
class Customer implements DocumentInterface
{
    use DocumentTrait;

    /**
     * @var string
     *
     * @ES\Property(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * 
     * @ES\Property(name="email", type="string", analyzer="simple")
     */
    private $email;

    // Setters and getters boilerplate follows:
    // ...
}
````
> This is the basic example only, for more information about mapping, please take a look at [Mapping](mapping.md) chapter.


Step 6: Create index
-----------------------------

Elastic search bundle provides several `CLI` commands. One of it is for creating index, run command in your terminal

````bash
app/console es:index:create 
````

> More about the  rest of the commands can be found in [Commands](commands.md) chapter.


Step 7: Use your new bundle
-----------------------------

Usage documentation for the Elasticsearch bundle is available [here](usage.md).
