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
 * Class Suggester.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Suggester extends AbstractProperty
{
    /**
     * @var string
     */
    public $type = 'completion';

    /**
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * @var string
     */
    public $objectName = 'ONGR\ElasticsearchBundle\Document\Suggestions';

    /**
     * @var string
     */
    public $indexAnalyzer;

    /**
     * @var string
     */
    public $searchAnalyzer;

    /**
     * @var int
     */
    public $preserveSeparators;

    /**
     * @var bool
     */
    public $preservePositionIncrements;

    /**
     * @var int
     */
    public $maxInputLength;

    /**
     * @var bool
     */
    public $payloads;

    /**
     * @var array<array>
     */
    public $context;
}
