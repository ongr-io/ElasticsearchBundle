# Mapping

Elasticsearch bundle requires mapping definitions in order for it to work with complex operations, like insert and update documents, do a full text search and etc.

### Mapping configuration

Here's an example of configuration containing the definitions of filter and analyzer:

```yaml

ongr_elasticsearch:
    analysis:
        filter:
            incremental_filter:
                type: edge_ngram
                min_gram: 1
                max_gram: 20
        analyzer:
            incrementalAnalyzer:
                type: custom
                tokenizer: standard
                filter:
                    - lowercase
                    - incremental_filter
    connections:
        default:
            index_name: acme_index
            analysis:
                analyzer:
                    - incrementalAnalyzer
                filter:
                    - incremental_filter
    managers:
        default:
            connection: default
            mappings:
                - AppBundle

```

At the very top you can see `analysis` node. This is for holding a filters, analyzers, tokenizers and other analyzation kind stuff for your connections. So lets assume you defined custom `incrementalAnalyzer` analyzer. The key stands as analyzer name, so down below in `default` connection's `analysis` section you can add this analyzer to include in certain connection mapping. And all you need to do is only to add the name. So now when you have defined a custom analyzer, you can use it in some document fields, see below in the document's examples how to do that.

In the managers configuration `mappings` is optional. If there are no mappings defined, it will look up through `Document` folders contained in the all bundles.


### Document class annotations

Lets start with a document class example.
```php
// src/AppBundle/Document/Content.php
namespace AppBundle/Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\DocumentTrait;

/**
 * @ES\Document(type="content")
 */
class Content
{
    use DocumentTrait;

    /**
     * @ES\Property(type="string")
     */
    public $title;
}
```

> You can use `DocumentTrait` trait to quickly add support for meta fields.


#### Document annotation configuration

- `@ES\Document(type="content")` Annotation defines that this class will represent elasticsearch type with name `content`.

- `type` parameter is for type name. This parameter is optional, if there will be no parameter set Elasticsearch bundle will create a type with lowercased class name.

##### Additional parameters:

-  **TTL (time to live)** - `_ttl={"enabled": true}` parameter with which you can enable documents to have time to live, also it you can set default time interval. To do this add `default` e.g.: `_ttl={"enabled": true, "default": "1d"}`. After time runs out document deletes itself automatically.

e.g. `@ES\Document(type="content", _ttl={"enabled": true, "default": "1d"})`

> You can use time units specified in `elasticsearch documentation`. ESB parses it if needed, e.g. for type mapping update.

##### DocumentTrait

`DocumentTrait` provides support for Elasticsearch meta fields (`_id`, `_source`,
`_ttl`, `_parent`, etc ). `DocumentTrait` has all parameters and setters already defined for you.


### Document properties annotations

To define type properties there is `@ES\Property` annotation. The only required
attribute is `type` - Elasticsearch field type to define what kind of information
will be indexed. By default field name is generated from property name by converting
it to "snake case" string. You can specify custom name by setting `name` attribute.

To add custom settings to property like analyzer it has to be included in `options`. Analyzers names is the same that was defined in `config.yml` `analysis` section [before](#Mapping configuration). Here's an example how to add it:

```php
// src/AppBundle/Document/Content.php
namespace AppBundle/Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\DocumentTrait;

/**
 * @ES\Document(type="content")
 */
class Content
{
    use DocumentTrait;

    /**
     * @ES\Property(
        type="string",
        name="original_title",
        options={"index_analyzer":"incrementalAnalyzer"}
      )
     */
    public $title;
}

```

> `options` container accepts any parameters. We leave mapping validation to elasticsearch and elasticsearch-php client, if there will be a mistake index won't be created due exception.


It is a little different to define nested and object types. For this user will need to create a separate class with object annotation. Lets assume we have a Content type with object field.

```php
// src/AppBundle/Document/Content.php
namespace AppBundle/Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\DocumentTrait;

/**
 * @ES\Document(type="content")
 */
class Content
{
    use DocumentTrait;

    /**
     * @ES\Property(type="string")
     */
    public $title;

    /**
     * @var ContentMetaObject
     *
     * @ES\Embedded(class="AppBundle:ContentMetaObject")
     */
    public $metaObject;
}

```

And the content object will look like:

```php
// src/AppBundle/Document/ContentMetaObject.php
namespace AppBundle/Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Object
 */
class ContentMetaObject
{
    /**
     * @ES\Property(type="string")
     */
    public $key;

    /**
     * @ES\Property(type="string")
     */
    public $value;
}

```

##### Multiple objects

As shown in the example, by default only a single object will be saved in the document.
If there is necessary to store a multiple objects (array), add `multiple=true`. While
initiating a document with multiple items you need to initialize property with new instance of `Collection`.

```php
// src/AppBundle/Document/Content.php

namespace AppBundle/Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Collection;

/**
 * @ES\Document(type="content")
 */
class Content
{
    // ...

    /**
     * @var ContentMetaObject[]|Collection
     *
     * @ES\Embedded(class="AppBundle:ContentMetaObject", multiple="true")
     */
    public $metaObjects;
    
    /**
     * Initialize collection.
     */
    public function __construct()
    {
        $this->metaObjects = new Collection();
    }
}
```

Insert action will look like this:
```php

<?php
$content = new Content();
$content->metaObjects[] = new ContentMetaObject();
$content->metaObjects[] = new ContentMetaObject();

$manager->persist($content);
$manager->commit();

```
To define object or nested fields use `@ES\Embedded` annotation. In the objects there is possibility to define other objects also.

> Nested types can be defined the same way as objects, except `@ES\Nested` annotation must be used.

More info about mapping is in the [elasticsearch mapping documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html)
