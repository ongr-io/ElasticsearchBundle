<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Test;

/**
 * Wraps an object and delays every method call.
 */
class DelayedObjectWrapper
{
    /**
     * @var object
     */
    private $object;

    /**
     * @var int Microseconds.
     */
    private $delay;

    /**
     * Wraps an object.
     *
     * @param object $object Object to wrap.
     * @param int    $delay  Delay after calls in seconds.
     */
    public function __construct($object, $delay = 0.5)
    {
        $this->object = $object;
        $this->delay = (int)($delay * 1000000);
    }

    /**
     * Calls a method from object with delay.
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($name, $args)
    {
        usleep($this->delay);

        return call_user_func_array([$this->object, $name], $args);
    }

    /**
     * Static function for creating this class instance.
     *
     * @param object $object
     * @param float  $delay
     *
     * @return DelayedObjectWrapper
     */
    public static function wrap($object, $delay = 0.5)
    {
        $name = __CLASS__;

        return new $name($object, $delay);
    }
}
