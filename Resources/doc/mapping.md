# Mapping

Elasticsearch bundle requires mapping definitions for it to work with complex operations,
like insert and update documents, do a full-text search, etc.

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
            incrementalAnalyzer:  #-> analyzer name
                type: custom
                tokenizer: standard
                filter:
                    - lowercase
                    - incremental_filter
    managers:
        default:
            index:
                index_name: your_index_name
                hosts:
                    - 127.0.0.1:9200
            mappings:
                - AppBundle
```

From 5.0 version mapping was enchased, and now you can change documents directory. See the example below:

```yml
#...
    managers:
        custom_dir:
            index:
                index_name: your_index_name
                hosts:
                    - 127.0.0.1:9200
            mappings:
                AppBundle: ~ #Document dir will be Document.
                CustomBundle:
                    document_dir: Entity #For this bundle will search documents in the Entity.
                    
        default:
            index:
                index_name: your_index_name
                hosts:
                    - 127.0.0.1:9200
            mappings:
                - AppBundle
```

> Both mappings are valid. In the above example, you can change the directory for the particular
 bundles where to find documents. Default dir remains `Document`.

At the very top, you can see `analysis` node. It represents [Elasticsearch analysis][1]. 
You can define here analyzers, tokenizers, token filters and character filters.
Once you define any analysis, then it can be used in any document mapping.

e.g. let's say you want to use incremental analyzer and custom lowercase filter analyzer in your index.
The elasticsearch settings mapping would like this:

```json
//PUT my_index
{
    "settings": {
        "analysis": {
            "filter": {
                "incremental_filter": {
                    "type": "edge_ngram",
                    "min_gram": "1",
                    "max_gram": "100"
                }
            },
            "analyzer": {
                "keywordAnalyzer": {
                "filter": [
                    "lowercase"
                ],
                "type": "custom",
                "tokenizer": "keyword"
            },
            "incrementalAnalyzer": {
                "filter": [
                    "lowercase",
                    "asciifolding",
                    "incremental_filter"
                ],
                "type": "custom",
                "tokenizer": "standard"
                }
            }
        }
    }
}
```


The representation of this particular example in the elasticsearch configuration:


```yaml
ongr_elasticsearch:
    analysis:
        analyzer:
            keywordAnalyzer:
                type: custom
                tokenizer: keyword
                filter: [lowercase]
            incrementalAnalyzer:
                type: custom
                tokenizer: standard
                filter:
                    - lowercase
                    - asciifolding
                    - incremental_filter
        filter:
            incremental_filter:
                type: edge_ngram
                min_gram: 1
                max_gram: 100
    managers:
        default:
            index:
                index_name: your_index_name
                hosts:
                    - 127.0.0.1:9200
            mappings:
                - AppBundle
```

### Document class annotations

Lets start with a document class example.

```php
// src/AppBundle/Document/Content.php

namespace AppBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Document(type="product")
 */
class Product
{
    /**
     * @ES\Property(type="text", name="title_in_es")
     */
    private $title;

    /**
     * Sets title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
```

> It is not mandatory to have private properties, and public will work as well.
 However, we firmly recommend using private according to OOP best practices.  

#### Document annotation configuration

- `@ES\Document(type="product")` Annotation defines that this class will represent elasticsearch type with name `content`.
- You can append any valid elasticsearch type options to the `options` variable.
 E.g. if you want to add `enable:false` it will look like this: `@ES\Document(type="product", options={"enable":"false"})`
- `type` parameter is for type name. This parameter is optional, if there will be no parameter set,
ElasticsearchBundle will create a type with lower cased class name.


### Properties annotations

For defining type properties, there is a `@ES\Property` annotation. The only required
attribute is `type` - Elasticsearch field type to specify what kind of information
will be indexed. By default, the field name is generated from property name by converting
it to "snake case" string. You can specify a custom name by setting the `name` attribute.

> Read more about elasticsearch supported types [in the official documentation][2].

To add a custom setting for the property like analyzer include it in the `options` variable.
Analyzers names must be defined in `config.yml` under the `analysis` node (read more in the topic above).
Here's an example how to add it:

```php
// src/AppBundle/Document/Product.php
namespace AppBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Document()
 */
class Product
{
    // ...

    /**
     * @ES\Property(
        type="text",
        options={"analyzer":"incrementalAnalyzer"}
      )
     */
    private $title;
    
    //....
```

>  `options` container accepts any parameters in annotation array format. We leave mapping validation
 to elasticsearch and elasticsearch-php client. If there will be invalid format annotations reader will throw exception,
 otherwise elasticsearch-php or elasticsearch database will throw an exception if something is wrong.


#### Object and Nested types

To define a nested or object type you have to use `@ES\Embedded` annotation and create a separate
class for this annotation. Here's an example, lets assume we have a `Product` type with `Variant` object field.

```php
// src/AppBundle/Document/Product.php

namespace AppBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Document()
 */
class Product
{
    /**
     * @ES\Property(type="string")
     */
    private $title;

    /**
     * @var ContentMetaObject
     *
     * @ES\Embedded(class="AppBundle:CategoryObject")
     */
    private $category;

    //...
}
```

And the `Category` object will look like:

```php
// src/AppBundle/Document/CategoryObject.php

namespace AppBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\ObjectType
 */
class CategoryObject
{
    /**
     * @ES\Property(type="string")
     */
    private $title;

    //...
}

```

> Class name can be anything, we called it `CategoryObject` to make it more readable. Notice that it is an object, not a document.

For this particular example the mapping in elasticsearch will look like this:

```json
 {
    "product": {
        "properties": {
            "title": {
                "type": "text"
            },
            "category": {
                "type": "object",
                "properties": {
                    "title": {
                        "type": "string"
                    }
                }
            }
        }
    }
}
```

##### Saving documents with relations

To insert a document with mapping from example above you have to create 2 objects:
 
```php
 
  $category = new CategoryObject();
  $category->setTitle('Jeans');
  
  $product = new Product();
  $product->setTitle('Orange Jeans');
  $product->setCategoryObject($category);
  
  //manager to work with elasticsearch index
  $manager->persist($product);
  $manager->commit();
 
```

##### Multiple objects

As shown in the example above, by ElasticsearchBundle default, only a single object will be saved in the document.
Meanwhile, Elasticsearch database doesn't care if in an object is stored as a single value or as an array. 
If it is necessary to store multiple objects (array), you have to add `multiple=true` to the annotation. While
initiating a document with multiple items you need to initialize property with the new instance of `ArrayCollection()`.

Here's an example:

```php
// src/AppBundle/Document/Product.php

namespace AppBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Document()
 */
class Product
{
    /**
     * @ES\Property(type="string")
     */
    private $title;

    /**
     * @var ContentMetaObject
     *
     * @ES\Embedded(class="AppBundle:VariantObject", multiple=true)
     */
    private $variants;
    
    public function __construct()
    {
        $this->variants = new ArrayCollection();
    }
    
    /**
     * Adds variant to the collection.
     *
     * @param VariantObject $variant
     * @return $this
     */
    public function addVariant(VariantObject $variant)
    {
        $this->variants[] = $variant;

        return $this;
    }
    
    //...
}
```

And the object:


```php
// src/AppBundle/Document/VariantObject.php

namespace AppBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\ObjectType
 */
class VariantObject
{
    /**
     * @ES\Property(type="string")
     */
    private $color;

    //...
}

```

Insert action will look like this:
```php
<?php
  
  $product = new Product();
  $product->setTitle('Orange Jeans');
  
  $variant = new VariantObject();
  $variant->setColor('orange');
  $product->addVariant($variant);

  $variant = new VariantObject();
  $variant->setColor('red');
  $product->addVariant($variant);

  $manager->persist($product);
  $manager->commit();
```

> There is no bounds to define other objects within objects.

> Nested types can be defined the same way as objects, except `@ES\Nested` annotation must be used.

The difference between `@ES\Embedded` and `@ES\Nested` is in the way that the Elasticsearch indexes them.
While the values of the fields in embedded objects are extracted and put into the same array with all the other
values of other embedded objects in the same field, during the indexation process, the values of the fields of
nested objects stored separately. This introduces differences when querying and filtering the index.

### Multi field annotations and usage

Within the properties annotation, you can specify the `field` attribute. It enables you to map several core
types of the same value. This can come very handy, e.g. when you want to map a text type with analyzed and
not analyzed values.

Lets take a look at example below:
```php
    /**
     * @var string
     * @ES\Property(
     *  type="text",
     *  name="title",
     *  options={
     *    "analyzer"="keywordAnalyzer",
     *    "fields"={
     *        "raw"={"type"="keyword"},
     *        "standard"={"type"="text", "analyzer"="standard"}
     *    }
     *  }
     * )
     */
    public $title;
```

The mapping in elasticsearch would look like this:

```json
{
    "product": {
        "properties": {
            "title": {
                "type": "text",
                "analyzer": "keywordAnalyzer",
                "fields": {
                    "raw": {
                        "type": "keyword"
                    },
                    "standard": {
                        "type": "text",
                        "analyzer": "standard"
                    }
                }
            }
        }
    }
}
```

You will notice that now title value is mapped both with and without the analyzer. Querying these fields will
look like this:

```php
        $query = new TermQuery('title', 'Bar');
        $search->addQuery($query);

        $result1 = $repo->execute($search);

        $query = new MatchQuery('title.raw', 'Bar');
        $search->addQuery($query);

        $result2 = $repo->execute($search);

        $query = new MatchQuery('title.standard', 'Bar');
        $search->addQuery($query);

        $result3 = $repo->execute($search);
```

### Meta-Fields Annotations

There are specialized meta fields that introduce different behaviours of elasticsearch.
Read the dedicated page about meta-field annotations [here](http://docs.ongr.io/ElasticsearchBundle/meta_fields).

> More information about mapping can be found in the [Elasticsearch mapping documentation][3].

[1]: https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis.html
[2]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-fields.html
[3]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html
