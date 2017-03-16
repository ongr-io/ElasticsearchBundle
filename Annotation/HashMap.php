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

use Doctrine\Common\Annotations\Annotation\Enum;

/**
 * Annotation for property which points to inner object.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class HashMap
{
    const NAME = 'hash_map';

    /**
     * Name of the type field. Defaults to normalized property name.
     *
     * @var string
     */
    public $name;

    /**
     * Property type for nested structure values.
     *
     * @var mixed
     * @Enum({
     *     "text", "keyword",
     *     "long", "integer", "short", "byte", "double", "float",
     *     "date",
     *     "boolean",
     *     "binary",
     *     "geo_point", "geo_shape",
     *     "ip", "completion", "token_count", "murmur3", "attachments", "percolator"
     * })
     */
    public $type;

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
            array_merge(
                [
                    'type' => $this->type
                ],
                $this->options
            ),
            $exclude
        );
    }
}
