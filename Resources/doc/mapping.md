# Mapping

Elasticsearch bundle requires mapping definitions for it to work with complex operations,
like insert and update documents, do a full-text search, etc.

### Mapping configuration

Here's an example of configuration containing the definitions of a filter and analyzer:

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
    indexes:
        App\Document\MyDocument:
            alias: my_index
```

At the very top, you can see `analysis` node. It represents [Elasticsearch analysis](https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis.html). 
Here you can define analyzers, tokenizers, token filters and character filters.
Once you define any analysis, then it can be used in any document mapping.

e.g. let's take a look how to use incremental analyzer and custom lowercase filter analyzer in your index.
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
```

> there is two ways to define index, you can pass all configuration through annotations or yml config. 
You can find more information about analysis at [the elasticsearch docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis.html). 

### Document class annotations

Lets start with a document class example.

```php
// src/Document/Content.php

namespace App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Index(alias="my_index", default=true)
 */
class MyIndex
{
    /**
     * @ES\Property(type="text", analyzer="keywordAnalyzer")
     */
    private $title;
    
    /**
     * @ES\Property(type="text", analyzer="incrementalAnalyzer")
     */
    private $description;
    
    //...
}
```

> It is not mandatory to have private properties, public will work as well.
 However, we firmly recommend using private according to OOP best practices.  

#### Document/Class annotation configuration

`@ES\Index` Annotation has these parameters:

- `alias` - which represent what alias will be created for a newly created index via cli command using `-a` parameter.
- `hosts` - here you can define elasticsearch hosts array, default is `127.0.0.1:9200`.
- `default` - makes this index default for cli commands, in that case it is not necessary to define document namespace.
    If you have only one index int the whole app that one will be default even if you not set default to true.
- `numberOfShards` - number of shard for the index.
- `numberOfReplicas` - number of replicas for the index.


### Property annotation configuration

For defining type properties, there is a `@ES\Property` annotation. The only required
attribute is `type` - Elasticsearch field type to specify what kind of information
will be indexed. By default, the field name is generated from property name by converting
it to "snake case" string. You can specify a custom name by setting the `name` attribute.

Here's the list of all available parameters:
- `name` - elasticsearch field name which maps to this variable name.
- `analyzer` - analyzer name to use from the list of analyzers configuration of built it analyzer from elastic.
- `searchAnalyzer` - the same as analyzer but dedicated for search.
- `searchQuoteAnalyzer` - the same as analyzer but dedicated for search quote.
- `fields` - allow to define additional fields with different analyzers within the same field.

> Read more about elasticsearch supported types [in the official documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-fields.html).


```php
// src/Document/Product.php
namespace App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Index(alias="my_index")
 */
class MyIndex
{
    // ...

    /**
     * @ES\Property(
        type="text",
        analyzer="incrementalAnalyzer"
     })
     */
    private $title;
    
    //....
```

#### Object and Nested types

To define a nested or object type you have to use `@ES\Embedded` annotation and create a separate
class for this annotation. Here's an example, lets assume we have a `Product` type with `CategoryObject` as object field.

```php
// src/AppBundle/Document/Product.php

namespace App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Index(alias="product")
 */
class Product
{
    /**
     * @ES\Property(type="text")
     */
    private $title;

    /**
     * @var ContentMetaObject
     *
     * @ES\Embedded(class="App\Document\CategoryObject")
     */
    private $category;

    //...
    
    public function __construct()
    {
        $this->category = new ArrayCollection();
    }
    
    public funtion addCategory($category)
    {
        $this->category->add($category)
    }

    //...
}
```

And the `Category` object will look like (it's a separate class):

```php
// src/Document/CategoryObject.php

namespace App\Document;

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

> Class name can be anything, we called it `CategoryObject` to make it more readable. Notice that it is an `ObjectType`, not an `Index`.

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
                        "type": "text"
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
  $product->addCategory($category);
  
  //manager to work with elasticsearch index
  $index->persist($product);
  $index->commit();
 
```

> Please notice that objects always are collections, no matter if you have one or multiple. 
  Previously we tried to separate it by introducing parameter, but it causes so much confusion and complexity, so from v6 it is unified.
##### Multiple objects

Here's an example:

```php
// src/Document/Product.php

namespace App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Index()
 */
class Product
{
    /**
     * @ES\Property(type="text")
     */
    private $title;

    /**
     * @var ContentMetaObject
     *
     * @ES\Embedded(class="App\Document\VariantObject")
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
// src/Document/VariantObject.php

namespace App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\ObjectType
 */
class VariantObject
{
    /**
     * @ES\Property(type="text")
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

> Nested types can be defined the same way as objects, except `@ES\NestedType` annotation must be used.

The difference between `@ES\ObjectType` and `@ES\NestedType` is in the way that the Elasticsearch indexes them.
While the values of the fields in embedded objects are extracted and put into the same array with all the other
values of other embedded objects in the same field, during the indexation process, the values of the fields of
nested objects stored separately. This introduces differences when querying and filtering the index.

More information about nested documents if [here](https://www.elastic.co/guide/en/elasticsearch/reference/current/nested.html)

### Multi field annotations and usage

Within the properties annotation, you can specify the `fields` attribute. It enables you to map several core
types of the same value. This can come very handy, e.g. when you want to map a text type with analyzed and
not analyzed values.

Lets take a look at example below:
```php
    /**
     * @var string
     * @ES\Property(
     *  type="text",
     *  name="title",
     *  analyzer="incrementalAnalyzer",
     *  fields={
     *    "keyword"={"type"="keyword"},
     *    "text"={"type"="text", "analyzer"="standard"}
     *    "anything_else"={"type"="text", "analyzer"="custom"}
     *  }
     * )
     */
    public $title;
```

> More information can be found [in the elasticsearch docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/multi-fields.html). 

The mapping in elasticsearch would look like this:

```json
{
    "product": {
        "properties": {
            "title": {
                "type": "text",
                "analyzer": "incrementalAnalyzer",
                "fields": {
                    "keyword": {
                        "type": "keyword"
                    },
                    "text": {
                        "type": "text",
                        "analyzer": "standard"
                    },
                    "anything_else": {
                        "type": "text",
                        "analyzer": "custom"
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
        //..
        $query = new TermQuery('title', 'Bar');
        $search->addQuery($query);

        $result1 = $repo->execute($search);

        $query = new MatchQuery('title.keyword', 'Bar');
        $search->addQuery($query);

        $result2 = $repo->execute($search);

        $query = new MatchQuery('title.text', 'Bar');
        $search->addQuery($query);

        $result3 = $index->execute($search);
```

### Meta-Fields Annotations

There are specialized meta fields that introduce different behaviours of elasticsearch.
Read the dedicated page about meta-field annotations [here](http://docs.ongr.io/ElasticsearchBundle/meta_fields).

> More information about mapping can be found in the [Elasticsearch mapping documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html).
