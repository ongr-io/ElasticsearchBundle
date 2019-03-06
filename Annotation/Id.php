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
 * Annotation to associate document property with _id meta-field.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Id extends AbstractAnnotation implements MetaFieldInterface, PropertiesAwareInterface
{
    const NAME = '_id';

    public function getName(): ?string
    {
        return self::NAME;
    }
}
