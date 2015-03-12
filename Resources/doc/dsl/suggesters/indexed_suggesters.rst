Indexed suggesters
==================

Indexed suggesters require special mapping and documented data to work,
this includes two suggesters: completion and context.

Mapping
-------

Completion
~~~~~~~~~~

Starting with a more simple example, completion suggester. Completion suggester is a simple field which you would just define in a document or an object using a special annotation for suggesters. Available mapping parameters can be found `here`_. For example:

.. code:: php

    <?php

    namespace Acme\DemoBundle\Document;

    use ONGR\ElasticsearchBundle\Annotation as ES;
    use ONGR\ElasticsearchBundle\Document\AbstractDocument;

    /**
     * Product document.
     *
     * @ES\Document(type="product")
     */
    class Product extends AbstractDocument
    {
        /**
         * @var CompletionSuggesting
         *
         * @ES\Suggester\CompletionSuggesterProperty(
         *  name = "completion_suggesting",
         *  objectName = "AcmeDemoBundle:CompletionSuggesting",
         *  index_analyzer = "simple",
         *  search_analyzer = "simple",
         *  payloads = false,
         *  )
         */
        public $completionSuggesting;
    }

As you can see we need to define an object for it so we can store and retrieve its' data objectively. To do that, we can just use a trait already prepared in ESB.

.. code:: php

    <?php

    namespace Acme\DemoBundle\Document;

    use ONGR\ElasticsearchBundle\Annotation as ES;
    use ONGR\ElasticsearchBundle\Document\Suggester\CompletionSuggesterInterface;
    use ONGR\ElasticsearchBundle\Document\Suggester\CompletionSuggesterTrait;

    /**
     * Suggesting document for testing.
     *
     * @ES\Object
     */
    class CompletionSuggesting implements CompletionSuggesterInterface
    {
        use CompletionSuggesterTrait;
    }

Context
~~~~~~~

Context suggester not only uses the `parameters`_ used in completion suggester, but also additional context mapping, into which you’ll store your data. To do this, ESB uses special annotation objects.

Here’s an example:

.. code:: php

    <?php

    namespace Acme\DemoBundle\Document;

    use ONGR\ElasticsearchBundle\Annotation as ES;
    use ONGR\ElasticsearchBundle\Document\AbstractDocument;

    /**
     * Product document.
     *
     * @ES\Document(type="product")
     */
    class Product extends AbstractDocument
    {
        /**
         * @var PriceLocationSuggesting
         *
         * @ES\Suggester\ContextSuggesterProperty(
         *   name = "suggestions",
         *   objectName = "AcmeDemoBundle:PriceLocationSuggesting",
         *   payloads = true,
         *   context = {
         * @ES\Suggester\Context\GeoLocationContext(name="location", precision = "5m", neighbors = true, default = "u33"),
         * @ES\Suggester\Context\CategoryContext(name="price", default = {"red", "green"}, path = "description")
         *   }
         * )
         */
        public $contextSuggesting;
    }

Parameters for geo context can be found
`here <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/suggester-context.html#_geo_location_mapping>`__,
and for category context can be found
`here <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/suggester-context.html#_category_mapping>`__.

As you can see it also requires object to store data, here’s an example.

.. note:: Context parameter name must be context in order for this mapping to work!

.. code:: php

    <?php

    namespace Acme\DemoBundle\Document;

    use ONGR\ElasticsearchBundle\Annotation as ES;
    use ONGR\ElasticsearchBundle\Document\Suggester\ContextSuggesterInterface;
    use ONGR\ElasticsearchBundle\Document\Suggester\ContextSuggesterTrait;

    /**
     * Suggesting document.
     *
     * @ES\Object
     */
    class PriceLocationSuggesting implements ContextSuggesterInterface
    {
        use ContextSuggesterTrait;

        /**
         * @var object
         *
         * @ES\Property(type="object", objectName="AcmeDemoBundle:PriceLocationContext", name="context")
         */
        private $context;
    }

And you also need to define your context object, for example:

.. note:: type doesn't matter here.

.. code:: php

    <?php

    namespace Acme\DemoBundle\Document;

    use ONGR\ElasticsearchBundle\Annotation as ES;

    /**
     * SuggestingContext document.
     *
     * @ES\Object
     */
    class PriceLocationContext
    {
        /**
         * @var string
         *
         * @ES\Property(name="price", type="string")
         */
        public $price;

        /**
         * @var array
         *
         * @ES\Property(name="location", type="string")
         */
        public $location;
    }

Storing
-------

Now that mapping is created, you can store data for each suggester into elasticsearch index. This is quite simple.

Example:

.. code:: php

    <?php

    $categoryContext = new PriceLocationContext();
    $categoryContext->price = '500';
    $categoryContext->location = ['lat' => 50, 'lon' => 50];
    $suggester = new PriceLocationSuggesting();
    $suggester->setInput(['test']);
    $suggester->setOutput('success');
    $suggester->setContext($categoryContext);
    $suggester->setPayload(['test']);
    $suggester->setWeight(50);

    $completionSuggester = new CompletionSuggesting();
    $completionSuggester->setInput(['a', 'b', 'c']);
    $completionSuggester->setOutput('completion success');
    $completionSuggester->setWeight(30);

    $product = new Product();
    $product->contextSuggesting = $suggester;
    $product->completionSuggesting = $completionSuggester;

    $manager->persist($product);
    $manager->commit();

To receive your data, search for it, just like you would with any other object.

Suggesting
----------

Once you have data and mapping, you can start looking for suggestions, it’s just like with any other suggesters, an example:

.. code:: php

    <?php

    $geoContext = new Context\GeoContext('location', ['lat' => 0, 'lon' => 0]);
    $categoryContext = new Context\CategoryContext('price', '500');
    $context = new Context('suggestions', 'cons');
    $context->addContext($geoContext);
    $context->addContext($categoryContext);
    $suggesters = [
        $context,
        new Completion('completion_suggesting', 'ipsum'),
    ];
    $results = $repository->suggest($suggesters);

.. _here: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-completion.html#completion-suggester-mapping
.. _parameters: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-completion.html#completion-suggester-mapping
