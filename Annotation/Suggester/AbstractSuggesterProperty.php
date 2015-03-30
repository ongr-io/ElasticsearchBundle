<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Annotation\Suggester;

use Doctrine\Common\Annotations\Annotation\Required;
use Ongr\ElasticsearchBundle\Annotation\AbstractProperty;

/**
 * Abstract class for various suggester annotations.
 */
abstract class AbstractSuggesterProperty extends AbstractProperty
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
     *
     * @Required
     */
     public $objectName;

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
}
