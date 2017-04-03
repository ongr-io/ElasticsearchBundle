<?php

namespace ONGR\ElasticsearchBundle\Result;

class ObjectCallbackIterator extends \ArrayIterator
{
    /**
     * @var \Closure
     */
    private $callback;

    /**
     * Converts array data to document objects via the callback function.
     *
     * @param \Closure $callback
     * @param array $array
     */
    public function __construct(\Closure $callback, array $array = array())
    {
        $this->callback = $callback;

        parent::__construct($array);
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
            $callback = $this->callback;
            return $callback($key);
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
            $callback = $this->callback;
            return $callback($offset);
        }

        return $value;
    }
}
