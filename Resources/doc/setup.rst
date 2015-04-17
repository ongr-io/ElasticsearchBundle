Setup
=====

Look at `beginners guide <guide.html>`_ for basic example.

Step 1: Install Elasticsearch bundle
------------------------------------

Elasticsearch bundle is installed using `Composer <https://getcomposer.org>`_.

.. code:: bash

    php composer.phar require ongr/elasticsearch-bundle "~0.1"

.. note:: Instructions for installing and deploying Elasticsearch can be found in `Elasticsearch installation page <https://www.elastic.co/downloads/elasticsearch/>`_.

Step 2: Enable Elasticsearch bundle
-----------------------------------

Enable Elasticsearch bundle in your AppKernel:

.. code:: php

    <?php
    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
        );
    }

.. _esb-manager:

Step 3: Add configuration
-------------------------

Add minimal configuration for Elasticsearch bundle.

.. code:: yaml

    #app/config/config.yml
    ongr_elasticsearch:
        connections:
            default:
                hosts:
                    - { host: 127.0.0.1:9200 }
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

.. note:: This is the basic example only, for more information about mapping, please take a look at `mapping <mapping.html>`_ chapter.

In this particular example we defined connections and managers. As you noticed it could be combined together.

Every connection handles Elasticsearch index and contains it's client. In the configuration tree connections array defines hosts, index name and settings.

Node ``managers`` configures document managers that are responsible for working with types (executing queries, persisting documents, etc). Managers can be accessed through service tag. E.g. ``es.manager`` (alias of ``es.manager.default``), ``es.manager.customer`` (defined ``customer`` manager).

Step 4: Define your types as ``Documents``
------------------------------------------

Elasticsearch bundle uses ``Document`` objects to communicate with elasticsearch objects. Now create a ``Customer`` class in the ``Document`` folder.

.. note:: Folder name could be changed in config settings

.. code:: php

    <?php
    namespace Acme\AcmeDemoBundle\Document;

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

        /**
         * @var string
         * 
         * @ES\Property(name="email", type="string", analyzer="simple")
         */
        private $email;

        // Setters and getters boilerplate follows:
        // ...
    }

.. note:: This is the basic example only, for more information about mapping, please take a look at `mapping <mapping.html>`_ chapter.

Step 6: Create index and mappings
---------------------------------

Elasticsearch bundle provides several ``CLI`` commands. One of them is for creating index, run command in your terminal:

.. code:: bash

    app/console es:index:create

Another command is used for putting mappings into elasticsearch client. Run the following commandin your terminal:

.. code:: bash

    app/console es:type:create

.. note:: More about the rest of the commands can be found in `commands <commands.html>`_ chapter.

Step 7: Use your new bundle
---------------------------

Usage documentation for the Elasticsearch bundle is available `here <usage.html>`_.

