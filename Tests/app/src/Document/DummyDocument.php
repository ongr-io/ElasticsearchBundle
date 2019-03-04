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

/**
 * Dummy index document for the functional testing.
 *
 * @ES\Index(alias="dummy")
 */
class DummyDocument
{
    /**
     * @ES\Id()
     */
    private $id;

    /**
     * @ES\Routing()
     */
    private $routing;

    /**
     * @ES\Property(
     *  type="text",
     *  name="title",
     *  settings={
     *    "fields"={
     *        "raw"={"type"="keyword"},
     *        "increment"={"type"="text", "analyzer"="incrementalAnalyzer"}
     *    }
     *  }
     * )
     */
    public $title;

    /**
     * @ES\Property(type="keyword", name="private")
     */
    private $privateField;

    /**
     * @ES\Embedded(class="ONGR\App\Document\CollectionNested", name="nested_collection")
     */
    public $nestedCollection;

    /**
     * @ES\Embedded(class="ONGR\App\Document\CollectionObject")
     */
    public $objectCollection;

    public function __construct()
    {
        $this->nestedCollection = new ArrayCollection();
        $this->objectCollection = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): DummyDocument
    {
        $this->id = $id;
        return $this;
    }

    public function getRouting(): string
    {
        return $this->routing;
    }

    public function setRouting(string $routing): DummyDocument
    {
        $this->routing = $routing;
        return $this;
    }

    public function getPrivateField(): string
    {
        return $this->privateField;
    }

    public function setPrivateField(string $privateField): DummyDocument
    {
        $this->privateField = $privateField;
        return $this;
    }
}
