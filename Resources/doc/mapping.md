Elasticsearch bundle requires mapping definition in order for it to work with index.

### Mapping configuration

Here's  an example of configuration with tokenizer's, filters and analyzer's definitions:

````yml
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
````

In the settings node user may choose a specific configuration for that connection with index settings.

In managers configuration mappings is optional. If there are no mappings defined it will look up through all bundles `Document` folders.


### Document mapping

````php
//AcmeTestBundle:Content
use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Document\DocumentTrait;

/**
 * Holds content page data.
 *
 * @ES\Document(type="content")
 */
class content implements DocumentInterface
{
    use DocumentTrait;

    /**
     * @var string
     *
     * @ES\Property(name="slug", type="string", index="not_analyzed")
     */
    public $slug;

    /**
     * @var string
     *
     * @ES\Property(name="title", type="string", search_analyzer="standard")
     */
    public $title;

    /**
     * @var string
     *
     * @ES\Property(name="content", type="string")
     */
    public $content;
}
````

>Important: be sure your @ES\Document class'es implements DocumentInterface, otherwise it will not work.

`@ES\Document(type="content")` Annotation defines that this class will represent elasticsearch type.
`type` parameter is for type name. This parame is optional, if there will be no param set Elasticsearch bundle will create type with lovercase class name. Additional params: 
  * **TTL (time to live)** - `_ttl={"enabled": true, "default": "1d"}` param with which you can enable documents to   have time to live and set default time interval. After time runs out document deletes itself automatically.

`DocumentTrait` includes support with all special fields in elasticsearch document such as `_id`, `_source`, `_ttl`, `_parent` handling.
 `DocumentTrait` has all parameters and setters already defined for you. Once there will be _ttl set Elasticsearch bundle will handle it automatically.

 To define type properties there is `@ES\Property` annotation. You can define different name than a property name and it will be handled automatically by bundle.
 Property also supports the type where you need to define what kind of information will be indexed. Additionally its also available to set `index`, `index_analyzer`, `search_analyzer`.
 Analyzers names is the same that was defined in `config.yml` before.

 It is little different to define nested and object types. For this user will need to create a separate class with object annotation.
 Lets assume we have a Content type with object field.

 ````php
 //AcmeTestBundle:Content

 use ONGR\ElasticsearchBundle\Annotation as ES;
 use ONGR\ElasticsearchBundle\Document\DocumentInterface;
 use ONGR\ElasticsearchBundle\Document\DocumentTrait;

 /**
  * Holds content page data.
  *
  * @ES\Document(type="content")
  */
 class Content implements DocumentInterface
 {
     use DocumentTrait;

     /**
      * @var string
      *
      * @ES\Property(name="title", type="string", search_analyzer="standard")
      */
     public $title;

     /**
      * @var string
      *
      * @ES\Property(name="meta", type="object", objectName="AcmeTestBundle:ContentMetaObject")
      */
     public $properties;
 }
 ````

 To define an object:

  ````php
  //AcmeTestBundle:ContentMetaObject

  use ONGR\ElasticsearchBundle\Annotation as ES;

  /**
   * Holds contents meta object data.
   *
   * @ES\Object
   */
  class ContentMetaObject implements DocumentInterface
  {
      /**
       * @var string
       *
       * @ES\Property(name="title", type="string", index="not_analyzer")
       */
      public $key;

      /**
       * @var string
       *
       * @ES\Property(name="value", type="string", index="not_analyzer")
       */
      public $value;
  }
  ````

  To define object fields the same `@ES\Property` annotations could be used. In the objects there is possibility  to define other objects.

  > Nested types can be defined the same way as objects, except @ES\Nested annotation must be used.
