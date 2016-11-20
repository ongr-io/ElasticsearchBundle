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
 * Annotation used to check mapping type during the parsing process.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Property
{
    /**
     * Field type.
     *
     * @var string
     *
     * @Doctrine\Common\Annotations\Annotation\Required
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
     * Name of the type field. Defaults to normalized property name.
     *
     * @var string
     */
    public $name;

    /**
     * In this field you can define options (like analyzers and etc) for specific field types.
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
