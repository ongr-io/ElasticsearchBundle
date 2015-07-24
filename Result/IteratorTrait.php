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

/**
 * Class CountableTrait.
 */
trait IteratorTrait
{
    /**
     * Return the current element.
     *
     * @return mixed
     */
    public function current()
    {
        return $this->getDocument($this->getKey());
    }

    /**
     * Move forward to next element.
     */
    public function next()
    {
        $this->advanceKey();
    }

    /**
     * Return the key of the current element.
     *
     * @return mixed
     */
    public function key()
    {
        return $this->getKey();
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->documentExists($this->getKey());
    }

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind()
    {
        $this->resetKey();
    }

    /**
     * @param mixed $getKey
     *
     * @return mixed
     */
    abstract protected function getDocument($getKey);

    /**
     * @return mixed
     */
    abstract protected function getKey();

    /**
     * Advances key.
     */
    abstract protected function advanceKey();

    /**
     * Checks if document exists.
     *
     * @param mixed $getKey
     *
     * @return mixed
     */
    abstract protected function documentExists($getKey);

    /**
     * Resets key.
     */
    abstract protected function resetKey();
}
