# Warmers

[Warmers][warmers_link] are used to run registered search requests to warm up the index before it is available for search.

## Registering a warmer
Warmers should implement `ONGR\ElasticsearchBundle\Cache\WarmerInterface` and loaded as a service with `es.warmer` tag. f.e.

```php
class FooWarmer implements WarmerInterface
{

    /**
     * {@inheritdoc}
     */
    public function warmUp(Search $search)
    {
        $search->addQuery(new MatchAllQuery());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'test_foo_warmer';
    }
}
```

Then register as a service (in this example yaml):

```yaml
services:
    vendor_bundle.foo.warmer:
        class: Vendor\Bundle\Warmer\FooWarmer
        tags:
            - { name: es.warmer, connection: "default,bar" }
```

As you notice we can define multiple connections to load warmers to, just be sure they are separated by commas and all surrounded by quotations marks.

## Putting warmers into index.

The easiet way to load warmers are through [Commands](commands.md).

Also we can load them manually. f.e.

```php
$manager->getConnection()->putWarmers();
```


[warmers_link]:http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-warmers.html
