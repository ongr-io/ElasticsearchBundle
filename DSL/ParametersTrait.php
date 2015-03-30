<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL;

/**
 * A trait which handles the behavior of parameters in queries, filters, etc.
 */
trait ParametersTrait
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * Checks if parameter exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Returns one parameter by it's name.
     *
     * @param string $name
     *
     * @return array|false
     */
    public function getParameter($name)
    {
        if ($this->hasParameter($name)) {
            return $this->parameters[$name];
        }

        return false;
    }

    /**
     * Returns an array of all parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string                 $name
     * @param array|string|\stdClass $value
     */
    public function addParameter($name, $value)
    {
        if (!$this->hasParameter($name)) {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * Sets an array of parameters.
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns given array merged with parameters.
     *
     * @param array $array
     *
     * @return array
     */
    protected function processArray(array $array = [])
    {
        return array_merge($array, $this->parameters);
    }
}
