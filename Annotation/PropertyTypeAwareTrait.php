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

trait PropertyTypeAwareTrait
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
}
