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

class ArrayIterator extends AbstractResultsIterator implements \ArrayAccess
{
    public function offsetExists($offset)
    {
        return $this->documentExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getDocument($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->documents[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->documents[$offset]);
    }

    protected function convertDocument(array $raw)
    {
        if (array_key_exists('_source', $raw)) {
            $doc = $raw['_source'];
        } elseif (array_key_exists('fields', $raw)) {
            $doc = array_map('reset', $raw['fields']);
        }

        $doc['_id'] = $raw['_id'];

        return $doc;
    }
}
