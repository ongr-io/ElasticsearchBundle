Indexed suggesters
==================

Indexed suggesters require special mapping and documented data to work,
this includes two suggesters: completion and context.

Mapping
-------

Completion
~~~~~~~~~~

Starting with a more simple example, completion suggester.
Available mapping parameters can be found `here`_. For example:

.. code:: php

    <?php

    namespace Acme\DemoBundle\Document;

    use ONGR\ElasticsearchBundle\Annotation as ES;
    use ONGR\ElasticsearchBundle\Document\AbstractDocument;
    use ONGR\ElasticsearchBundle\Document\Suggestions;

    /**
     * Product document.
     *
     * @ES\Document(type="product")
     */
    class Product extends AbstractDocument
    {
        /**
         * @var Suggestions
         *
         * @ES\Suggester(
         *  name = "completion_suggesting",
         *  index_analyzer = "simple",
         *  search_analyzer = "simple",
         *  payloads = false,
         *  )
         */
        public $completionSuggesting;
    }

Context
~~~~~~~

Context suggester not only uses the `parameters`_ used in completion suggester, but also additional context mapping,
into which you’ll store your data.

Here’s an example:

.. code:: php

    <?php

    namespace Acme\DemoBundle\Document;

    use ONGR\ElasticsearchBundle\Annotation as ES;
    use ONGR\ElasticsearchBundle\Document\AbstractDocument;
    use ONGR\ElasticsearchBundle\Document\Suggestions;

    /**
     * Product document.
     *
     * @ES\Document(type="product")
     */
    class Product extends AbstractDocument
    {
        /**
         * @var Suggestions
         *
         * @ES\Suggester(
         *   name = "suggestions",
         *   payloads = true,
         *   context = {
         *      "location" : {
         *          "type" : "geo",
         *          "precision" : "5m",
         *          "neighbors" : true,
         *          "default" : "u33"
         *      },
         *      "price" : {
         *          "type" : "category",
         *          "default" : {"red", "green"},
         *          "path" : "description"
         *      }
         *   }
         * )
         */
        public $contextSuggesting;
    }

..

Parameters for geo context can be found
`here <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/suggester-context.html#_geo_location_mapping>`__,
and for category context can be found
`here <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/suggester-context.html#_category_mapping>`__.

Storing
-------

Now that mapping is created, you can store data for each suggester into elasticsearch index. This is quite simple.

Example:

.. code:: php

    <?php

    $suggester = new Suggestions();
    $suggester->setInput(['test']);
    $suggester->setOutput('success');
    $suggester->addContext('price', 500);
    $suggester->addContext('location', ['lat' => 50, 'lon' => 50]);
    $suggester->setPayload(['test']);
    $suggester->setWeight(50);

    $completionSuggester = new Suggestions();
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

Once you have data and mapping, you can start looking for suggestions, it’s just like with any other suggesters,
an example:

.. code:: php

    <?php

    $contextSuggester = new Suggester(Suggester::TYPE_CONTEXT, 'suggestions', 'cons');
    $contextSuggester->addContext(new Context('price', 500));
    $contextSuggester->addContext(new Context('location', ['lat' => 0, 'lon' => 0], Context::TYPE_GEO_LOCATION));

    $completionSuggester = new Suggester(Suggester::TYPE_COMPLETION, 'completion_suggesting', 'ipsum');

    $results = $repository->suggest([$contextSuggester, $completionSuggester]);

..

.. _here: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-completion.html#completion-suggester-mapping
.. _parameters: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-completion.html#completion-suggester-mapping
