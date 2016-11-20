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
 * Class DocumentIterator.
 */
class ArrayIterator extends AbstractResultsIterator implements \ArrayAccess
{
    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->documentExists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->getDocument($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->documents[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->documents[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        if (array_key_exists('_source', $document)) {
            return $document['_source'];
        } elseif (array_key_exists('fields', $document)) {
            return array_map('reset', $document['fields']);
        }

        return $document;
    }
}
