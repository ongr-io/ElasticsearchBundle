<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result;

use ONGR\ElasticsearchBundle\ORM\Repository;

/**
 * RawResultScanIterator class.
 */
class RawResultScanIterator extends RawResultIterator
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var string
     */
    private $scrollDuration;

    /**
     * @var string
     */
    private $scrollId;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * Sets a Repository.
     *
     * @param Repository $repository
     *
     * @return DocumentScanIterator
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Sets scroll duration.
     *
     * @param string $scrollDuration
     *
     * @return DocumentScanIterator
     */
    public function setScrollDuration($scrollDuration)
    {
        $this->scrollDuration = $scrollDuration;

        return $this;
    }

    /**
     * Sets scroll ID.
     *
     * @param string $scrollId
     *
     * @return DocumentScanIterator
     */
    public function setScrollId($scrollId)
    {
        $this->scrollId = $scrollId;

        return $this;
    }

    /**
     * Returns scroll ID.
     *
     * @return string
     */
    public function getScrollId()
    {
        return $this->scrollId;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->getTotalCount();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->documents[parent::key()];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        parent::next();

        $this->key++;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if ($this->key > 0) {
            throw new \LogicException('Scan iterator can not be rewound more than once.');
        }

        parent::rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        if (parent::key() !== null) {
            return true;
        }

        if ($this->key >= $this->getTotalCount()) {
            return false;
        }

        $this->rawData = $this->repository->scan(
            $this->scrollId,
            $this->scrollDuration,
            Repository::RESULTS_RAW
        );
        $this->setScrollId($this->rawData['_scroll_id']);

        $this->documents = &$this->rawData['hits']['hits'];

        return reset($this->documents) !== false;
    }
}
