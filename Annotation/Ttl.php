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
 * Annotation used to enable TTL and associate document property with _ttl meta-field.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Ttl implements MetaFieldInterface
{
    /**
     * Parent document class name.
     *
     * @var string
     */
    public $default;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '_ttl';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return [
            'enabled' => true,
            'default' => $this->default,
        ];
    }
}
