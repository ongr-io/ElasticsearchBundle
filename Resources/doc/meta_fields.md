Using Elasticsearch Meta-Fields
===

This page is about document metadata also known as meta-fields. For detailed
explanation on what it is and how it works read official Elasticsearch
[documentation][1]. Below we explain how to use meta-fields supported
by this bundle.

Supported Meta-Fields
---

### @Id (_id)

None of meta-fields is mandatory, but probably you will be using `_id` more than
others. This meta-field works both ways. When you read document from Elasticsearch
you get document's ID in associated property. Anytime you want to change document's ID
or set custom ID for new document, just set value to this property and it will be
stored in Elasticsearch. `_id` meta-field is represented by `@Id` annotation:

```php     
use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Document()
 */
class Person
{
    /**
     * @ES\Id()
     */
    public $id;

    // ...
}
```

### @ParentDocument (_parent)

`_parent` meta-field is used to create a parent-child relationship between two mapping
types. It is represented by `@ParentDocument` annotation. The only one and required
attribute is `class`, where you need to specify class of parent document.

```php     
    /**
     * @ES\ParentDocument(class="AppBundle:Family")
     */
    public $parent;
```

### @Ttl (_ttl)

This field allows to configure how long a document should live before it is automatically
deleted. After you add property with `@Ttl` annotation, TTL feature is automatically enabled. 

```php     
    /**
     * @ES\Ttl()
     */
    public $ttl;
```

You can set default TTL with optional `default` attribute:

```php     
    /**
     * @ES\Ttl(default="5m")
     */
    public $ttl;
```

Example above sets default TTL to 5 minutes. See Elasticsearch [documentation][2]
for supported time value formats.

> __Note:__ The current `_ttl` implementation is deprecated by Elasticsearch and
> will be replaced with a different implementation in a future version.

[1]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-fields.html
[2]: https://www.elastic.co/guide/en/elasticsearch/reference/current/common-options.html#time-units
