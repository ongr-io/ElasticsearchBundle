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
use ONGR\ElasticsearchBundle\Collection\Collection;

/**
 * Product document for testing.
 *
 * @ES\Document(type="product", ttl={"enabled"=true})
 */
class Product
{
    /**
     * @var string
     *
     * @ES\Id()
     */
    private $id;

    /**
     * @var string
     *
     * @ES\Ttl()
     */
    private $ttl;

    /**
     * @var string
     *
     * @ES\Routing()
     */
    private $routing;

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
    private $title;

    /**
     * @var string
     * @ES\Property(type="string", name="description")
     */
    private $description;

    /**
     * @var CategoryObject
     * @ES\Embedded(class="AcmeBarBundle:CategoryObject")
     */
    private $category;

    /**
     * @var CategoryObject[]
     * @ES\Embedded(class="AcmeBarBundle:CategoryObject", multiple=true)
     */
    private $relatedCategories;

    /**
     * @var int
     * @ES\Property(type="float", name="price")
     */
    private $price;

    /**
     * @var string
     * @ES\Property(type="geo_point", name="location")
     */
    private $location;

    /**
     * @var string
     * @ES\Property(type="boolean", name="limited")
     */
    private $limited;

    /**
     * @var \DateTime
     * @ES\Property(type="date", name="released")
     */
    private $released;

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
    private $tokenPiecesCount;

    public function __construct()
    {
        $this->relatedCategories = new Collection();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @param string $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return CategoryObject
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param CategoryObject $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return CategoryObject[]
     */
    public function getRelatedCategories()
    {
        return $this->relatedCategories;
    }

    /**
     * @param CategoryObject[] $relatedCategories
     */
    public function setRelatedCategories($relatedCategories)
    {
        $this->relatedCategories = $relatedCategories;
    }

    /**
     * @param CategoryObject $relatedCategory
     */
    public function addRelatedCategory($relatedCategory)
    {
        $this->relatedCategories[] = $relatedCategory;
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param int $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getLimited()
    {
        return $this->limited;
    }

    /**
     * @param string $limited
     */
    public function setLimited($limited)
    {
        $this->limited = $limited;
    }

    /**
     * @return \DateTime
     */
    public function getReleased()
    {
        return $this->released;
    }

    /**
     * @param \DateTime $released
     */
    public function setReleased($released)
    {
        $this->released = $released;
    }

    /**
     * @return int
     */
    public function getTokenPiecesCount()
    {
        return $this->tokenPiecesCount;
    }

    /**
     * @param int $tokenPiecesCount
     */
    public function setTokenPiecesCount($tokenPiecesCount)
    {
        $this->tokenPiecesCount = $tokenPiecesCount;
    }

    /**
     * @return string
     */
    public function getRouting()
    {
        return $this->routing;
    }

    /**
     * @param string $routing
     */
    public function setRouting($routing)
    {
        $this->routing = $routing;
    }


}
