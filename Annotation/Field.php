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
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Annotation used to map field elements to documents
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Field
{

    /**
     * Field type.
     *
     * @var string
     *
     * @Required
     * @Enum({"string", "boolean", "integer", "float", "long", "short", "byte", "double", "date",
     *        "geo_point", "geo_shape", "ip", "binary", "token_count" })
     */
    public $type;

    /**
     * Name of the type field. Defaults to normalized property name.
     *
     * @var string
     */
    public $name;

}
