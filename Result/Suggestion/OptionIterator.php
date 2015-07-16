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

use ONGR\ElasticsearchBundle\Result\Suggestion\Option\CompletionOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\PhraseOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\SimpleOption;
use ONGR\ElasticsearchBundle\Result\Suggestion\Option\TermOption;

/**
 * Suggestions results holder.
 */
class OptionIterator implements \ArrayAccess, \Iterator
{
    /**
     * @var array
     */
    private $rawData = [];

    /**
     * @var SimpleOption[]
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
     * @return SimpleOption|null
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
     * @return SimpleOption
     */
    private function convert($data)
    {
        if (isset($data['freq'])) {
            return new TermOption($data['text'], $data['score'], $data['freq']);
        }

        if (isset($data['highlighted'])) {
            return new PhraseOption($data['text'], $data['score'], $data['highlighted']);
        }

        if (isset($data['payload'])) {
            return new CompletionOption($data['text'], $data['score'], $data['payload']);
        }

        return new SimpleOption($data['text'], $data['score']);
    }
}
