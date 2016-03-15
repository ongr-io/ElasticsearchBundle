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

use ONGR\ElasticsearchBundle\Collection\Collection;

/**
 * ObjectIterator class.
 */
class ObjectIterator extends Collection
{
    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var array Aliases information.
     */
    private $alias;

    /**
     * @var array
     */
    private $rawObjects;

    /**
     * Using part of abstract iterator functionality only.
     *
     * @param Converter $converter
     * @param array     $objects
     * @param array     $alias
     */
    public function __construct($converter, $objects, $alias)
    {
        $this->converter = $converter;
        $this->rawObjects = $objects;
        $this->alias = $alias;

        $callback = function ($v) {
            return null;
        };

        // Pass array with available keys and no values
        parent::__construct(array_map($callback, $objects));
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $this->converter->assignArrayToObject(
            $document,
            new $this->alias['namespace'](),
            $this->alias['aliases']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $value = parent::current();

        // Generate objects on demand
        if ($value === null && $this->valid()) {
            $key = $this->key();
            $value = $this->convertDocument($this->rawObjects[$key]);
            $this->rawObjects[$key] = null;
            $this->offsetSet($key, $value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $value = parent::offsetGet($offset);

        // Generate objects on demand
        if ($value === null && $this->valid()) {
            $value = $this->convertDocument($this->rawObjects[$offset]);
            $this->rawObjects[$offset] = null;
            $this->offsetSet($offset, $value);
        }

        return $value;
    }
}
