<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\DocumentTrait;

/**
 * Product document for testing.
 *
 * @ES\Document(type="product")
 */
class Product
{
    use DocumentTrait;

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

    /**
     * @var string
     * @ES\Property(type="string", name="description")
     */
    public $description;

    /**
     * @var CategoryObject
     * @ES\Property(type="object", name="category", objectName="AcmeBarBundle:CategoryObject")
     */
    public $category;

    /**
     * @var CategoryObject[]
     * @ES\Property(type="object", name="related_categories", multiple=true, objectName="AcmeBarBundle:CategoryObject")
     */
    public $relatedCategories;

    /**
     * @var int
     * @ES\Property(type="float", name="price")
     */
    public $price;

    /**
     * @var string
     * @ES\Property(type="geo_point", name="location")
     */
    public $location;

    /**
     * @var string
     * @ES\Property(type="boolean", name="limited")
     */
    public $limited;

    /**
     * @var \DateTime
     * @ES\Property(type="date", name="released")
     */
    public $released;

    /**
     * @var int
     *
     * @ES\Property(
     *     type="string",
     *     name="pieces_count",
     *     options={
     *        "fields"={
     *          "count"={"type"="token_count", "analyzer"="whitespace"}
     *        }
     *     }
     * )
     */
    public $tokenPiecesCount;
}
