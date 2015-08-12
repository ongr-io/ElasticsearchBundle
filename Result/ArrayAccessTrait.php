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
 * Trait ArrayAccessTrait.
 */
trait ArrayAccessTrait
{
    /**
     * Whether a offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->documentExists($offset);
    }

    /**
     * Offset to retrieve.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getDocument($offset);
    }

    /**
     * Offset to set.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->addDocument($value, $offset);
    }

    /**
     * Offset to unset.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->removeDocument($offset);
    }

    /**
     * Checks if document exits.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    abstract protected function documentExists($offset);

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    abstract protected function getDocument($offset);

    /**
     * Removes document.
     *
     * @param mixed $offset
     */
    abstract protected function removeDocument($offset);

    /**
     * @param mixed $value
     * @param mixed $offset
     */
    abstract protected function addDocument($value, $offset);
}
