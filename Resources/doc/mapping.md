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

/**
 * @ES\Document(type="content")
 */
class Content
{
    /**
     * @var string
     *
     * @ES\Id()
     */
    public $id;

    /**
     * @ES\Property(type="string")
     */
    public $title;
}
```

#### Document annotation configuration

- `@ES\Document(type="content")` Annotation defines that this class will represent elasticsearch type with name `content`.

- `type` parameter is for type name. This parameter is optional, if there will be no parameter set Elasticsearch bundle will create a type with lowercased class name.

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

/**
 * @ES\Document(type="content")
 */
class Content
{
    // ...

    /**
     * @ES\Property(
        type="string",
        name="original_title",
        options={"analyzer":"incrementalAnalyzer"}
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

/**
 * @ES\Document(type="content")
 */
class Content
{
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

### Multi field annotations and usage

Within the properties annotation you can specify the `field` attribute. It enables you to map several core_types of the same value.
This can come very handy, for example, when wanting to map a string type, once when it’s analyzed and once when it’s not_analyzed.
Lets take a look at an example.
```php
    /**
     * @var string
     * @ES\Property(
     *  type="string",
     *  name="title",
     *  options={
     *    "fields"={
     *        "raw"={"type"="string", "index"="not_analyzed"},
     *        "title"={"type"="string"}
     *    }
     *  }
     * )
     */
    public $title;

```
You will notice that now title value is mapped both with and without the analyzer. Querying these fields will
look like this:

```php
....
        $query = new MatchQuery('title.title', 'Bar');
        $search->addQuery($query);

        $result1 = $repo->execute($search);

        $query = new MatchQuery('title.raw', 'Bar');
        $search->addQuery($query);

        $result2 = $repo->execute($search);
....
```
As you can see, when querying, full navigation notation (`title.title`) has to be specified, not just `title`.

### Meta-Fields Annotations

Read dedicated page about meta-field annotations [here](meta_fields.md).

> More information about mapping can be found in the [Elasticsearch mapping documentation][1].

[1]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html
[2]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-fields.html
