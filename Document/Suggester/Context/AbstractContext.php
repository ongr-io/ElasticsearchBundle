<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Document\Suggester\Context;

/**
 * Abstract context type for context suggester.
 */
abstract class AbstractContext
{
    /**
     * Context name.
     *
     * @var string
     */
    private $name;

    /**
     * Returns context name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets context name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns context type.
     *
     * @return string
     */
    abstract public function getContextType();

    /**
     * Returns context object value.
     *
     * @return string
     */
    abstract public function getValue();
}
