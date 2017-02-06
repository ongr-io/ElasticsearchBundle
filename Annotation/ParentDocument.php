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
 * Annotation used to enable parent-child relationship.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class ParentDocument implements MetaField
{
    const NAME = '_parent';

    /**
     * Parent document class name.
     *
     * @var string
     *
     * @Doctrine\Common\Annotations\Annotation\Required
     */
    public $class;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return [
            'type' => null, // Actual value will be generated from $class property
        ];
    }
}
