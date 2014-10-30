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
 * Holds highlight data for a document.
 */
class DocumentHighlight implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $raw;

    /**
     * @param array $rawData
     */
    public function __construct($rawData)
    {
        $this->raw = $rawData;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if (array_key_exists($offset, $this->raw) && !empty($this->raw[$offset])) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \UnderflowException("Offset {$offset} undefined.");
        }

        return reset($this->raw[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Method not supported. Read only.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Method not supported.');
    }
}
