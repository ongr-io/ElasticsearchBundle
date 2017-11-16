<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Product document for testing.
 *
 * @ES\Document(
 *     options={
 *      "dynamic_templates"={
 *          {
 *              "custom_attributes_template"={
 *                  "path_match"="custom_attributes.*",
 *                  "mapping"={
 *                      "type"="text",
 *                      "analyzer"="keyword"
 *                  }
 *              }
 *          }
 *     }
 *    }
 * )
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
     * @ES\Routing()
     */
    private $routing;

    /**
     * @var string|array
     * @ES\Property(
     *  type="text",
     *  name="title",
     *  options={
     *    "fields"={
     *        "raw"={"type"="keyword"},
     *        "increment"={"type"="text", "analyzer"="incrementalAnalyzer"}
     *    }
     *  }
     * )
     */
    private $title;

    /**
     * @var string|array
     * @ES\Property(type="text", name="description")
     */
    private $description;

    /**
     * @var CategoryObject
     * @ES\Embedded(class="TestBundle:CategoryObject")
     */
    private $category;

    /**
     * @var CategoryObject[]
     * @ES\Embedded(class="TestBundle:CategoryObject", multiple=true)
     */
    private $relatedCategories;

    /**
     * @var float|array
     * @ES\Property(type="float", name="price")
     */
    private $price;

    /**
     * @var string|array
     * @ES\Property(type="geo_point", name="location")
     */
    private $location;

    /**
     * @var boolean
     * @ES\Property(type="boolean", name="limited")
     */
    private $limited;

    /**
     * @var array
     * @ES\HashMap(name="custom_attributes", type="text")
     */
    private $customAttributes;

    /**
     * @var \DateTime
     * @ES\Property(type="date", name="released")
     */
    private $released;

    /**
     * Product constructor.
     */
    public function __construct()
    {
        $this->relatedCategories = new ArrayCollection();
    }

    /**
     * @return string|array
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|array $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string|array
     */
    public function getRouting()
    {
        return $this->routing;
    }

    /**
     * @param string|array $routing
     */
    public function setRouting($routing)
    {
        $this->routing = $routing;
    }

    /**
     * @return string|array
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string|array $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string|array
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|array $description
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
     * @return float|array
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float|array $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return string|array
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string|array $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string|array
     */
    public function getLimited()
    {
        return $this->limited;
    }

    /**
     * @param string|array $limited
     */
    public function setLimited($limited)
    {
        $this->limited = $limited;
    }

    /**
     * @return array
     */
    public function getCustomAttributes()
    {
        return $this->customAttributes;
    }

    /**
     * @param array $customAttributes
     */
    public function setCustomAttributes($customAttributes)
    {
        $this->customAttributes = $customAttributes;
    }

    /**
     * @param mixed $customAttribute
     */
    public function addCustomAttribute($customAttribute)
    {
        $this->customAttributes[] = $customAttribute;
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
}
