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

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Dummy index document for the functional testing.
 *
 * @ES\Index(alias="dummy")
 */
class DummyDocument
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
    public $multipleAnalysis;

    /**
     * @var string
     * @ES\Property(type="keyword", name="private_field")
     */
    private $privateField;

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
