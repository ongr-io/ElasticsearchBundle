Mapping
=======

Elasticsearch bundle requires mapping definition in order for it to work with index.

Mapping configuration
---------------------

Here's an example of configuration containing the definitions of tokenizer, filter and analyzer:

.. code:: yaml

    elasticsearch:
        connections:
            default:
                hosts:
                    - { host: 127.0.0.1:9200 }
                index_name: acme_index
                settings:
                    number_of_shards: 2
                    number_of_replicas: 0
                    index:
                        refresh_interval: -1
                    analysis:
                        tokenizer:
                            pathTokenizer:
                                type : path_hierarchy
                                buffer_size: 2024
                                skip: 0
                                delimiter: /
                        filter:
                            incremental_filter:
                                type: edge_ngram
                                min_gram: 1
                                max_gram: 20
                        analyzer:
                            pathAnalyzer:
                                type: custom
                                tokenizer: pathTokenizer
                            urlAnalyzer:
                                type: custom
                                tokenizer: keyword
                                filter: [lowercase]
                            incrementalAnalyzer:
                                type: custom
                                tokenizer: standard
                                filter:
                                    - lowercase
                                    - incremental_filter
        managers:
            default:
                connection: default
                debug: true
            foo:
                connection: default
                debug: true
                readonly: true

In the settings node user may choose a specific configuration for that connection with index settings.

In managers configuration ``mappings`` is optional. If there are no mappings defined, it will look up through all ``Document`` folders contained in a bundle. If ``debug`` is set to true, profiler and logging will be enabled (by default it is set to false).

.. note::

    To make manager ``readonly`` set ``readonly: true`` - manager will be able to execute only readonly queries.

Document mapping
----------------

.. code:: php

    <?php
    //AcmeTestBundle:Content
    use ONGR\ElasticsearchBundle\Annotation as ES;
    use ONGR\ElasticsearchBundle\Document\AbstractContentDocument;

    /**
     * Holds content page data.
     *
     * @ES\Document(type="content")
     */
    class Content extends AbstractContentDocument
    {
        /**
         * @var string
         *
         * @ES\Property(type="string", name="header")
         */
        public $header;
    }

.. important:: be sure your @ES\\Document class’es directly implements DocumentInterface or extends AbstractDocument, otherwise it will not work.

``@ES\Document(type="content")`` Annotation defines that this class will represent elasticsearch type.
``type`` parameter is for type name. This parameter is optional, if there will be no parameter set Elasticsearch bundle will create type with lowercase class name. Additional parameters:

-  **TTL (time to live)** - ``_ttl={"enabled": true, "default": "1d"}`` parameter with which you can enable documents to have time to live and set default time interval. After time runs out document deletes itself automatically.

.. note:: You can use time units specified in `elasticsearch documentation`_. ESB parses it if needed, e.g. for type mapping update.

``AbstractDocument`` implements ``DocumentInterface`` and gives support with all special fields in elasticsearch document such as ``_id``, ``_source``, ``_ttl``, ``_parent`` handling. ``AbstractDocument`` has all parameters and setters already defined for you. Once there will be \_ttl set Elasticsearch bundle will handle it automatically.

To define type properties there is ``@ES\Property`` annotation. You can define different name than a property name and it will be handled automatically by bundle. Property also supports the type where you need to define what kind of information will be indexed. Additionally its also available to set ``index``, ``index_analyzer``, ``search_analyzer``. Analyzers names is the same that was defined in ``config.yml`` before.

It is little different to define nested and object types. For this user will need to create a separate class with object annotation. Lets assume we have a Content type with object field.

.. code:: php

    <?php
    //AcmeTestBundle:Content

    use ONGR\ElasticsearchBundle\Annotation as ES;
    use ONGR\ElasticsearchBundle\Document\AbstractDocument;

    /**
     * Holds content page data.
     *
     * @ES\Document(type="content")
     */
    class Content extends AbstractDocument
    {
        /**
         * @var string
         *
         * @ES\Property(name="title", type="string", search_analyzer="standard")
         */
        public $title;

        /**
         * @var ContentMetaObject
         *
         * @ES\Property(name="meta", type="object", objectName="AcmeTestBundle:ContentMetaObject")
         */
        public $property;
        
         /**
          * @var ContentMetaObject[]
          *
          * @ES\Property(name="meta_single", type="object", multiple=true, objectName="AcmeTestBundle:ContentMetaObject")
          */
         public $properties;
    }

To define an object:

.. code:: php

    <?php
    //AcmeTestBundle:ContentMetaObject

    use ONGR\ElasticsearchBundle\Annotation as ES;
    use ONGR\ElasticsearchBundle\Document\AbstractDocument;

    /**
     * Holds contents meta object data.
     *
     * @ES\Object
     */
    class ContentMetaObject extends AbstractDocument
    {
        /**
         * @var string
         *
         * @ES\Property(name="title", type="string", index="not_analyzed")
         */
        public $key;

        /**
         * @var string
         *
         * @ES\Property(name="value", type="string", index="not_analyzed")
         */
        public $value;
    }

.. note:: Multiple objects

    As shown in example, by default only a single object will be saved in your document. If you want multiple objects, you’ll have to set ``multiple=true``. While initiating a document with multiple items you can simply set an array or any kind of traversable.

    .. code:: php

        <?php
        $content = new Content();
        $content->properties = [new ContentMetaObject(), new ContentMetaObject()];

        $manager->persist($content);
        $manager->commit();

To define object fields the same ``@ES\Property`` annotations could be used. In the objects there is possibility to define other objects.

.. note:: Nested types can be defined the same way as objects, except ``@ES\Nested`` annotation must be used.

.. _elasticsearch documentation: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/mapping-ttl-field.html#_default
