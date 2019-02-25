<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation;

use ONGR\ElasticsearchBundle\Mapping\Caser;

/**
 * Annotation for property which points to inner object.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Embedded
{
    /**
     * Inner object class name.
     *
     * @var string Object name to map
     *
     * @Doctrine\Common\Annotations\Annotation\Required
     */
    public $class;

    /**
     * Name of the type field. Defaults to normalized property name.
     *
     * @var string
     */
    public $name;

    /**
     * In this field you can define options.
     *
     * @var array
     */
    public $settings;
}
