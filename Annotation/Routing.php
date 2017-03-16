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
 * Annotation used to enable ROUTING and associate document property with _routing meta-field.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Routing implements MetaField
{
    const NAME = '_routing';

    /**
     * @var bool
     */
    public $required = false;

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
            'required' => $this->required
        ];
    }
}
