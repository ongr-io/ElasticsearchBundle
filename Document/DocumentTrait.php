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

use ONGR\ElasticsearchBundle\Result\DocumentHighlight;

/**
 * Trait with common document fields and methods.
 *
 * @deprecated Use AbstractDocument instead, will remove in 1.0
 */
trait DocumentTrait
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $score;

    /**
     * @var string
     */
    public $parent;

    /**
     * @var string
     */
    public $ttl;

    /**
     * @var DocumentHighlight
     */
    public $highlight;

    /**
     * Legacy property support.
     *
     * @param string $property
     *
     * @return null|string
     */
    public function __get($property)
    {
        switch ($property) {
            case '_id':
                return $this->id;
            case '_score':
                return $this->score;
            case '_ttl':
                return $this->ttl;
            case '_parent':
                return $this->parent;
            default:
                return null;
        }
    }

    /**
     * Legacy property support and some special properties.
     *
     * @param string $property
     * @param mixed  $value
     */
    public function __set($property, $value)
    {
        switch ($property) {
            case '_id':
                $this->setId($value);
                break;
            case '_score':
                $this->setScore($value);
                break;
            case '_ttl':
                $this->setTtl($value);
                break;
            case '_parent':
                $this->setParent($value);
                break;
            default:
                $this->{$property} = $value;
                break;
        }
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
     * Sets highlight.
     *
     * @param DocumentHighlight $highlight
     */
    public function setHighlight(DocumentHighlight $highlight)
    {
        $this->highlight = $highlight;
    }

    /**
     * Returns highlight.
     *
     * @throws \UnderflowException
     *
     * @return DocumentHighlight
     */
    public function getHighLight()
    {
        if ($this->highlight === null) {
            throw new \UnderflowException('Highlight not set.');
        }

        return $this->highlight;
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
