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
     * Defines if related value will store a single object or an array of objects
     *
     * If this value is set to true, in the result ObjectIterator will be provided,
     * otherwise you will get single object.
     *
     * @var bool Object or ObjectIterator
     */
    public $multiple;

    /**
     * In this field you can define options.
     *
     * @var array
     */
    public $options;

    /**
     * {@inheritdoc}
     */
    public function dump(array $exclude = [])
    {
        $array = array_diff_key(
            array_filter(
                get_object_vars($this),
                function ($value) {
                    return $value || is_bool($value);
                }
            ),
            array_flip(array_merge(['class', 'name', 'multiple'], $exclude))
        );

        return array_combine(
            array_map(
                function ($key) {
                    return Caser::snake($key);
                },
                array_keys($array)
            ),
            array_values($array)
        );
    }
}
