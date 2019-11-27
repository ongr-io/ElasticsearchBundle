<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Annotation as ES;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Dummy index document for the functional testing.
 *
 * @ES\Index(alias="dummy", default=true)
 */
class DummyDocument
{
    // This con't is only as a helper.
    CONST INDEX_NAME = 'dummy';

    /**
     * @ES\Id()
     */
    public $id;

    /**
     * @ES\Routing()
     */
    public $routing;

    /**
     * @ES\Property(
     *  type="text",
     *  name="title",
     *  fields={
     *    "raw"={"type"="keyword"},
     *    "increment"={"type"="text", "analyzer"="incrementalAnalyzer"}
     *  }
     * )
     */
    public $title;

    /**
     * @ES\Property(type="keyword", name="private")
     */
    private $privateField;

    /**
     * @ES\Property(type="float")
     */
    public $number;

    /**
     * @ES\Embedded(class="ONGR\App\Document\CollectionNested", name="nested_collection")
     */
    private $nestedCollection;

    /**
     * @ES\Embedded(class="ONGR\App\Document\CollectionObject")
     */
    private $objectCollection;

    public function __construct()
    {
        $this->nestedCollection = new ArrayCollection();
        $this->objectCollection = new ArrayCollection();
    }

    public function getPrivateField(): ?string
    {
        return $this->privateField;
    }

    public function setPrivateField(string $privateField): DummyDocument
    {
        $this->privateField = $privateField;
        return $this;
    }

    public function getNestedCollection()
    {
        return $this->nestedCollection;
    }

    public function setNestedCollection($nestedCollection)
    {
        $this->nestedCollection = $nestedCollection;
        return $this;
    }

    public function getObjectCollection()
    {
        return $this->objectCollection;
    }

    public function setObjectCollection($objectCollection)
    {
        $this->objectCollection = $objectCollection;
        return $this;
    }
}
