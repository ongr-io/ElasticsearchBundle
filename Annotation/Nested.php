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
 * Annotation to mark a class as a nested type during the parsing process.
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @deprecated Object is reserved word in PHP 7.2 This class due Object class will be changed to NestedType as well.
 */
final class Nested
{
    const NAME = 'nested';

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
