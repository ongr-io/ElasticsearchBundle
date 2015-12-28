<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * This trait provides support for main Elasticsearch meta fields.
 */
trait DocumentTrait
{
    /**
     * @var string
     *
     * @ES\MetaField(name="_id")
     */
    public $id;

    /**
     * @var string
     *
     * @ES\MetaField(name="_score")
     */
    public $score;

    /**
     * @var string
     *
     * @ES\MetaField(name="_parent")
     */
    public $parent;

    /**
     * @var string
     *
     * @ES\MetaField(name="_ttl")
     */
    public $ttl;

    /**
     * When document is cloned id is set to null.
     */
    public function __clone()
    {
        $this->setId(null);
    }

    /**
     * Sets document unique id.
     *
     * @param string $documentId
     *
     * @return $this
     */
    public function setId($documentId)
    {
        $this->id = $documentId;

        return $this;
    }

    /**
     * Returns document id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets document score.
     *
     * @param string $documentScore
     *
     * @return $this
     */
    public function setScore($documentScore)
    {
        $this->score = $documentScore;

        return $this;
    }

    /**
     * Gets document score.
     *
     * @return string
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Sets parent document id.
     *
     * @param string $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns parent document id.
     *
     * @return null|string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Checks if document has a parent.
     *
     * @return bool
     */
    public function hasParent()
    {
        return $this->parent !== null;
    }

    /**
     * Sets time to live timestamp.
     *
     * @param string $ttl
     *
     * @return $this
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * Returns time to live value.
     *
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }
}
