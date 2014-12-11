<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation\Suggester\Context;

/**
 * Abstract class for various context annotations.
 */
abstract class AbstractContext
{
    /**
     * @var array
     */
    public $default;

    /**
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * @var string
     */
    public $path;

    /**
     * Returns context type.
     *
     * @return string
     */
    abstract public function getType();

    /**
     * Returns filtered object data.
     *
     * @return array
     */
    public function filter()
    {
        $vars = array_diff_key(
            array_filter(get_object_vars($this)),
            array_flip(['name'])
        );

        $vars['type'] = $this->getType();

        return $vars;
    }
}
