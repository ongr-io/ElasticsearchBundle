<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Annotation that can be used to define multi-field parameters.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
final class MultiField extends AbstractProperty
{
    /**
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * @var string
     *
     * @Required
     */
    public $type;

    /**
     * @var string
     */
    public $index;

    /**
     * @var string
     */
    public $analyzer;

    /**
     * @var string
     */
    public $indexAnalyzer;

    /**
     * @var string
     */
    public $searchAnalyzer;
}
