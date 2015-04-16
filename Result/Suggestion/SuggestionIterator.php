<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result\Suggestion;

/**
 * Suggestions results holder.
 */
class SuggestionIterator implements \ArrayAccess, \Iterator
{
    /**
     * @var array
     */
    private $rawData = [];

    /**
     * @var SuggestionEntry[]
     */
    private $convertedData = [];

    /**
     * Constructor.
     *
     * @param array $rawData
     */
    public function __construct($rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->rawData[$offset]) || isset($this->convertedData[$offset]);
    }

    /**
     * Offset to retrieve.
     *
     * @param string|int $offset
     *
     * @return SuggestionEntry[]|null
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        if (!isset($this->convertedData[$offset])) {
            $this->convertedData[$offset] = $this->convert($this->rawData[$offset]);
            $this->rawData[$offset] = null;
        }

        return $this->convertedData[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('Data of this iterator can not be changed after initialization.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('Data of this iterator can not be changed after initialization.');
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->offsetGet($this->key());
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->rawData);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->rawData);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->key() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        reset($this->rawData);
    }

    /**
     * Converts array to a array of suggestion objects.
     *
     * @param array $data
     *
     * @return SuggestionEntry[]
     */
    private function convert($data)
    {
        $out = [];
        foreach ($data as $entry) {
            $out[] = new SuggestionEntry(
                $entry['text'],
                $entry['offset'],
                $entry['length'],
                new OptionIterator($entry['options'])
            );
        }

        return $out;
    }
}
