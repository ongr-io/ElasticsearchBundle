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

use ONGR\ElasticsearchBundle\Service\Repository;

/**
 * Trait ScrollableTrait.
 */
trait ScrollableTrait
{
    use IteratorTrait{
        rewind as private traitRewind;
        valid as private traitValid;
    }

    /**
     * @var string
     */
    private $scrollDuration;

    /**
     * @var string
     */
    private $scrollId;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var bool
     */
    private $rewind = true;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @return string
     */
    public function getScrollDuration()
    {
        return $this->scrollDuration;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $valid = $this->traitValid();
        if ($valid) {
            return true;
        }

        $this->page();

        return $this->traitValid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if ($this->rewind) {
            $this->rewind = false;
            $this->traitRewind();
        } else {
            throw new \LogicException('Scrollable iterator can only be used once');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->getKey() + $this->getOffset();
    }

    /**
     * @return int
     */
    protected function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     *
     * @return $this
     */
    protected function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return int
     */
    protected function getCount()
    {
        return $this->getTotalCount();
    }

    /**
     * @param string $scrollDuration
     *
     * @return $this
     */
    public function setScrollDuration($scrollDuration)
    {
        $this->scrollDuration = $scrollDuration;

        return $this;
    }

    /**
     * @return string
     */
    public function getScrollId()
    {
        return $this->scrollId;
    }

    /**
     * @param string $scrollId
     *
     * @return $this
     */
    public function setScrollId($scrollId)
    {
        $this->scrollId = $scrollId;

        return $this;
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param Repository $repository
     *
     * @return $this
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Advances scan page.
     *
     * @return $this
     */
    protected function page()
    {
        if ($this->key() == $this->getCount()) {
            return $this;
        }

        $this->setOffset($this->getOffset() + $this->getKey());
        $this->clean();

        $raw = $this->repository->scan($this->getScrollId(), $this->getScrollDuration(), Repository::RESULTS_RAW);
        $this->setScrollId($raw['_scroll_id']);
        $this->setDocuments($raw['hits']['hits']);

        return $this;
    }

    /**
     * @return int
     */
    protected function getKey()
    {
        return $this->key;
    }

    /**
     * Advances key.
     *
     * @return $this
     */
    protected function advanceKey()
    {
        $this->key++;

        return $this;
    }

    /**
     * Resets key.
     *
     * @return $this
     */
    protected function resetKey()
    {
        $this->key = 0;

        return $this;
    }

    /**
     * Removes documents.
     */
    abstract protected function clean();

    /**
     * @param array $documents
     */
    abstract protected function setDocuments(&$documents);

    /**
     * @return int
     */
    abstract protected function getTotalCount();

    /**
     * @return bool
     */
    protected function getStoreConverted()
    {
        return false;
    }
}
