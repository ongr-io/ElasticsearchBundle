<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Annotation\Suggester\Context;

use Doctrine\Common\Annotations\Annotation\Required;
use Ongr\ElasticsearchBundle\Mapping\DumperInterface;

/**
 * Abstract class for various context annotations.
 */
abstract class AbstractContext implements DumperInterface
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
     * {@inheritdoc}
     */
    public function dump(array $exclude = [])
    {
        $vars = array_diff_key(
            array_filter(get_object_vars($this)),
            array_flip(['name'])
        );

        $vars['type'] = $this->getType();

        return $vars;
    }
}
