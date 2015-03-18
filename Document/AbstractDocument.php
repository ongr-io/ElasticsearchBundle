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
 * Document abstraction which introduces mandatory fields for the document.
 */
abstract class AbstractDocument implements DocumentInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $score;

    /**
     * @var string
     */
    private $parent;

    /**
     * @var string
     */
    private $ttl;

    /**
     * @var DocumentHighlight
     */
    private $highlight;

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
     * @return DocumentInterface
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
     * @return DocumentInterface
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
     * @return DocumentInterface
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
     * @return DocumentInterface
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
