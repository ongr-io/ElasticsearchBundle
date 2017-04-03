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

use Closure;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ObjectIterator class.
 */
class ObjectIterator extends ArrayCollection
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
     * @var \Closure
     */
    private $convertCallback;

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

        // On-demand conversion callback for ArrayAccess/IteratorAggregate
        $this->convertCallback = function ($key) {
            $value = $this->convertDocument($this->rawObjects[$key]);
            $this->rawObjects[$key] = null;
            $this->offsetSet($key, $value);
            return $value;
        };

        $callback = function ($v) {
            return null;
        };

        // Pass array with available keys and no values
        parent::__construct(array_map($callback, $objects));
    }

    /**
     * Converts all existing array values into their document equivalents.
     *
     * @return array
     */
    public function toArray()
    {
        $all = parent::toArray();
        $callback = $this->convertCallback;
        array_walk($all, function (&$value, $key) use ($callback) {
            if ($value === null) {
                $value = $callback($key);
            }
        });
        return $all;
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
     * Converts a raw object when the key for the object must be determined first.
     *
     * @param array $rawObject
     *
     * @return bool|object
     */
    protected function convertFromValue(array $rawObject)
    {
        if (false === $rawObject) {
            return false;
        }
        $callback = $this->convertCallback;
        $key = key($this->rawObjects);
        return $callback($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $callback = $this->convertCallback;
        return new ObjectCallbackIterator($callback, $this->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function first()
    {
        $first = parent::first();
        if ($first === null) {
            $first = reset($this->rawObjects);
            return $this->convertFromValue($first);
        }

        return $first;
    }

    /**
     * {@inheritdoc}
     */
    public function last()
    {
        $last = parent::last();

        if ($last === null) {
            $last = end($this->rawObjects);
            return $this->convertFromValue($last);
        }

        return $last;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $next = parent::next();

        if ($next === null) {
            $next = next($this->rawObjects);
            return $this->convertFromValue($next);
        }

        return $next;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $current = parent::current();

        if ($current === null) {
            $current = current($this->rawObjects);
            return $this->convertFromValue($current);
        }

        return $current;
    }

    /**
     * {@inheritdoc}
     */
    public function get($offset)
    {
        $value = parent::get($offset);

        // Generate objects on demand
        if ($value === null && $this->containsKey($this->key())) {
            $callback = $this->convertCallback;
            return $callback($offset);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        return array_values($this->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function map(Closure $func)
    {
        return new ArrayCollection(array_map($func, $this->toArray()));
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Closure $p)
    {
        return new ArrayCollection(array_filter($this->toArray(), $p));
    }

    /**
     * {@inheritdoc}
     */
    protected function createFrom(array $elements)
    {
        return new static($this->converter, $elements, $this->alias);
    }
}
