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

### @Routing (_routing)

Custom routing patterns can be implemented by specifying a custom routing value per document. 
The same routing value needs to be provided when getting, deleting, or updating the document.
It is represented by the `@Routing` annotation. Here is an example of such a field:

```php     
    /**
     * @ES\Routing()
     */
    public $routing;
```

Forgetting the routing value can lead to a document being indexed on more than one shard. 
As a safeguard, the _routing field can be configured to make a custom routing value 
required for all CRUD operations. This can be implemented by setting the `equired` 
attribute to true (`@ES\Routing(required=true)`).

More information on routing can be found in the [dedicated docs][2]

[1]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-fields.html
[2]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-routing-field.html
