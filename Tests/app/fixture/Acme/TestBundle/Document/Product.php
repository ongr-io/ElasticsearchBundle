<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Product document for testing.
 *
 * @ES\Document(type="product")
 * @ES\Skip({"name"})
 * @ES\Inherit({"price"})
 */
class Product extends Item
{
    /**
     * @var string
     *
     * @ES\Property(type="string", name="title", fields={@ES\MultiField(name="raw", type="string")})
     */
    public $title;

    /**
     * @var string
     *
     * @ES\Property(type="string", name="description")
     */
    public $description;

    /**
     * @var PriceLocationSuggesting
     *
     * @ES\Suggester\ContextSuggesterProperty(
     *   name="suggestions",
     *   objectName="AcmeTestBundle:PriceLocationSuggesting",
     *   payloads=true,
     *   context={
     * @ES\Suggester\Context\GeoLocationContext(name="location", precision="5m", neighbors=true, default="u33"),
     * @ES\Suggester\Context\CategoryContext(name="price", default={"red", "green"}, path="description")
     *   }
     * )
     */
    public $contextSuggesting;

    /**
     * @var CompletionSuggesting
     *
     * @ES\Suggester\CompletionSuggesterProperty(
     *  name="completion_suggesting",
     *  objectName="AcmeTestBundle:CompletionSuggesting",
     *  indexAnalyzer="simple",
     *  searchAnalyzer="simple",
     *  payloads=false,
     *  )
     */
    public $completionSuggesting;

    /**
     * @var int
     *
     * @ES\Property(type="integer", name="price")
     */
    public $price;

    /**
     * @var string
     *
     * @ES\Property(type="geo_point", name="location", geohash=true, geohashPrefix=true, geohashPrecision="1km")
     */
    public $location;

    /**
     * @var string
     *
     * @ES\Property(type="geo_shape", name="shape")
     */
    public $shape;

    /**
     * @var UrlObject[]|\Iterator
     *
     * @ES\Property(type="object", objectName="AcmeTestBundle:UrlObject", multiple=true, name="url")
     */
    public $links;

    /**
     * @var ImagesNested[]|\Iterator
     *
     * @ES\Property(type="nested", objectName="AcmeTestBundle:ImagesNested", multiple=true, name="images")
     */
    public $images;

    /**
     * @var Category[]|\Iterator
     *
     * @ES\Property(type="object", objectName="AcmeTestBundle:Category", multiple=true, name="categories")
     */
    public $categories;

    /**
     * @var string
     *
     * @ES\Property(type="ip", name="ip")
     */
    public $ip;

    /**
     * @var string
     *
     * @ES\Property(type="string", name="limited", index="not_analyzed", ignoreAbove=20)
     */
    public $limited;

    /**
     * @var string
     *
     * @ES\Property(type="string", name="stored", store=true, indexName="ongr-esb-test")
     */
    public $stored;
}
