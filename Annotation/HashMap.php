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
final class HashMap extends AbstractAnnotation
{
    const NAME = 'hash_map';

    use PropertyTypeAwareTrait;

    /**
     * Name of the type field. Defaults to normalized property name.
     *
     * @var string
     */
    public $name;
}
