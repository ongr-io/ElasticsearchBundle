<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Product document for testing.
 *
 * @ES\Document()
 */
class Product
{
    /**
     * @var string
     *
     * @ES\Id()
     */
    public $id;

    /**
     * @var string
     * @ES\Property(type="keyword", name="title")
     */
    public $title;

    /**
     * @var CategoryObject[]
     *
     * @ES\Embedded(class="TestBundle:CategoryObject", multiple=true)
     */
    public $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
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
     * @return CategoryObject[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param CategoryObject[] $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }
}
