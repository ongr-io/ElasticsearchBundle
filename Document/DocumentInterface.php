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

interface DocumentInterface
{
    /**
     * Sets document unique id.
     *
     * @param string $documentId
     *
     * @return DocumentInterface
     */
    public function setId($documentId);

    /**
     * Returns document id.
     *
     * @return string
     */
    public function getId();

    /**
     * Sets document score.
     *
     * @param string $documentScore
     *
     * @return DocumentInterface
     */
    public function setScore($documentScore);

    /**
     * Gets document score.
     *
     * @return string
     */
    public function getScore();

    /**
     * Sets parent document id.
     *
     * @param string $parent
     *
     * @return DocumentInterface
     */
    public function setParent($parent);

    /**
     * Returns parent document id.
     *
     * @return null|string
     */
    public function getParent();

    /**
     * Checks if document has a parent.
     *
     * @return bool
     */
    public function hasParent();

    /**
     * Sets time to live timestamp.
     *
     * @param int $ttl
     *
     * @return DocumentInterface
     */
    public function setTtl($ttl);

    /**
     * Returns time to live value.
     *
     * @return int
     */
    public function getTtl();

    /**
     * Returns highlight.
     *
     * @throws \UnderflowException
     *
     * @return DocumentHighlight
     */
    public function getHighlight();

    /**
     * Sets highlight.
     *
     * @param DocumentHighlight $highlight
     */
    public function setHighlight(DocumentHighlight $highlight);
}
