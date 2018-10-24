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

/**
 * Annotation to mark a class as an object during the parsing process.
 *
 * `Object` name as class name is forbidden in PHP 7 but we never create this
 *  class as object and only use it for annotation definition.
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @deprecated Object is reserved word in PHP 7.2, it will be changed to ObjectType class
 */
final class ObjectType
{
    const NAME = 'object';

    /**
     * In this field you can define options.
     *
     * @var array
     */
    public $options = [];

    /**
     * {@inheritdoc}
     */
    public function dump(array $exclude = [])
    {
        return array_diff_key(
            $this->options,
            $exclude
        );
    }
}
