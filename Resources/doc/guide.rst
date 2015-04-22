Beginners guide
===============

Before starting this, you should have already installed:

Text editor (`PhpStorm <https://www.jetbrains.com/phpstorm/download/>`_ or `Vim <http://vim.en.softonic.com/>`_, or `SublimeText <http://www.sublimetext.com/2>`_, or `BlueFish <http://bluefish.openoffice.nl/download.html>`_ or other..),

`Composer <https://getcomposer.org/download/>`_,

`Git <http://git-scm.com/downloads>`_,

`Symfony <https://github.com/symfony/symfony-installer>`_ (Preferred installation `here <https://github.com/symfony/symfony-installer>`_),

`ElasticSearch <https://www.elastic.co/downloads/elasticsearch>`_ (and launched it, so you can go to `localhost:9200 <localhost:9200>`_ and see it's working)

Create new Symfony project
--------------------------
Open Terminal, go to your working directory or create new

.. code:: bash

    cd ~
    mkdir Sites
    cd Sites

Create new Symfony project (You need to have installed Symfony with preferred installation)

.. code:: bash

    symfony new project

.. note:: You should create new project using exactly this way. If you create it using composer, (in the next chapter) inside config you need to change Bundle name (for ongr_elasticsearch managers mappings) and in later chapters it can confuse you.

Setup ElasticSearchBundle
-------------------------

Download ElasticsearchBundle. This version ``"~0.1"`` can be changed to newer.

.. code:: bash

    cd project
    composer require ongr/elasticsearch-bundle "~0.1"

Open Text editor and navigate to ``Sites/project/app/AppKernel.php``. Enable ElasticSearchBundle by adding this one line:

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

Go to ``app/config/config.yml``, add configuration at the very bottom:

.. code:: php

    ongr_elasticsearch:
        connections:
            default:
                hosts:
                    - { host: 127.0.0.1:9200 }
                index_name: product
                settings:
                    refresh_interval: -1
                    number_of_replicas: 1
        managers:
            default:
                connection: default
                mappings:
                    - AcmeDemoBundle

Creating Database for ElasticSearch
-----------------------------------

Go to ``src/Acme/DemoBundle`` and create new Folder ``Document``. It must be named as "Document". ONGR points to exactly this directory as default.

Go to ``src/Acme/DemoBundle/Document`` and create new File ``Product.php``.
Open this Product.php and paste that:

.. code:: php

    <?php
        namespace Acme\DemoBundle\Document;

        use ONGR\ElasticsearchBundle\Annotation as ES;
        use ONGR\ElasticsearchBundle\Document\AbstractDocument;

        /**
         * @ES\Document
         */
        class Product extends AbstractDocument
        {
            /**
             * @var integer
             *
             * @ES\Property(name="number", type="integer")
             */
            public $number;

            /**
             * @var string
             *
             * @ES\Property(name="name", type="string")
             */
            public $name;

            /**
             * @var string
             *
             * @ES\Property(name="place", type="string")
             */
            public $place;
        }

Open terminal and create new index (means Database)

.. code:: bash

    app/console es:index:create

Adding data to ElasticSearch
----------------------------

Go to ``src/Acme/DemoBundle`` and create new Folder ``Data``.

Go to ``src/Acme/DemoBundle/Data`` and create new File ``items.json``.
Paste this data there:

.. code:: json

    [
      {"count":25,"date":"2015-04-08T14:46:21+0200"},
      {"_type":"product","_id":"1","_source":{"name":"Amaryllis","place":"German", "number": 1}},
      {"_type":"product","_id":"2","_source":{"name":"Amaryllis","place":"England", "number": 2}},
      {"_type":"product","_id":"3","_source":{"name":"Amaryllis","place":"Greece", "number": 3}},
      {"_type":"product","_id":"4","_source":{"name":"Amaryllis","place":"Portugal", "number": 4}},
      {"_type":"product","_id":"5","_source":{"name":"Amaryllis","place":"French", "number": 5}},
      {"_type":"product","_id":"6","_source":{"name":"Clover","place":"German", "number": 6}},
      {"_type":"product","_id":"7","_source":{"name":"Clover","place":"England", "number": 7}},
      {"_type":"product","_id":"8","_source":{"name":"Clover","place":"Greece", "number": 8}},
      {"_type":"product","_id":"9","_source":{"name":"Clover","place":"Portugal", "number": 9}},
      {"_type":"product","_id":"10","_source":{"name":"Clover","place":"French", "number": 10}},
      {"_type":"product","_id":"11","_source":{"name":"Bluebell","place":"German", "number": 11}},
      {"_type":"product","_id":"12","_source":{"name":"Bluebell","place":"England", "number": 12}},
      {"_type":"product","_id":"13","_source":{"name":"Bluebell","place":"Greece", "number": 13}},
      {"_type":"product","_id":"14","_source":{"name":"Bluebell","place":"Portugal", "number": 14}},
      {"_type":"product","_id":"15","_source":{"name":"Bluebell","place":"French", "number": 15}},
      {"_type":"product","_id":"16","_source":{"name":"Iris","place":"German", "number": 16}},
      {"_type":"product","_id":"17","_source":{"name":"Iris","place":"England", "number": 17}},
      {"_type":"product","_id":"18","_source":{"name":"Iris","place":"Greece", "number": 18}},
      {"_type":"product","_id":"19","_source":{"name":"Iris","place":"Portugal", "number": 19}},
      {"_type":"product","_id":"20","_source":{"name":"Iris","place":"French", "number": 20}},
      {"_type":"product","_id":"21","_source":{"name":"Foxglove","place":"German", "number": 21}},
      {"_type":"product","_id":"22","_source":{"name":"Foxglove","place":"England", "number": 22}},
      {"_type":"product","_id":"23","_source":{"name":"Foxglove","place":"Greece", "number": 23}},
      {"_type":"product","_id":"24","_source":{"name":"Foxglove","place":"Portugal", "number": 24}},
      {"_type":"product","_id":"25","_source":{"name":"Foxglove","place":"French", "number": 25}}
    ]

Open terminal and update your index with this data:

.. code:: bash

    app/console es:index:import --raw src/Acme/DemoBundle/Data/items.json

You can now locate to `localhost:9200/product/product/1 <localhost:9200/product/product/1>`_ and you should see your first product.

.. note:: ElasticSearch comparison to MySQL: index = Database, type = Table. 

    In this example we created 25 Tables (all named product) inside one Database (named product).

Getting data from ElasticSearch
-------------------------------

Go to ``src/Acme/DemoBundle/Controller/WelcomeController.php``. Make it like that:

.. code:: php

    <?php

        namespace Acme\DemoBundle\Controller;

        use Symfony\Bundle\FrameworkBundle\Controller\Controller;

        class WelcomeController extends Controller
        {
            public function indexAction()
            {
                $manager = $this->get("es.manager");
                $repository = $manager->getRepository('AcmeDemoBundle:Product');
                $product = $repository->find(1);

                return $this->render('AcmeDemoBundle:Welcome:index.html.twig', array('product' => $product));
            }
        }

Go to ``src/Acme/DemoBundle/Resources/views/Welcome/index.html.twig``, delete everything and make it like that:

.. code:: php

    {% extends '::base.html.twig' %}
    {% block body -%}
        My product is: {{ dump(product) }}
    {% endblock %}

Open terminal and launch your server:

.. code:: bash

    php app/console server:run

Go to `localhost:8000 <localhost:8000>`_ and you should see your first product.