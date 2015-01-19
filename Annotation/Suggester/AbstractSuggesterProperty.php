<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation\Suggester;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Abstract class for various suggester annotations.
 */
abstract class AbstractSuggesterProperty
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
    public $index_analyzer;

    /**
     * @var string
     */
    public $search_analyzer;

    /**
     * @var int
     */
    public $preserve_separators;

    /**
     * @var bool
     */
    public $preserve_position_increments;

    /**
     * @var int
     */
    public $max_input_length;

    /**
     * @var bool
     */
    public $payloads;

    /**
     * Returns required properties.
     *
     * @param array $extraExclude Extra object variables to exclude.
     *
     * @return array
     */
    public function filter($extraExclude = [])
    {
        return array_diff_key(
            array_filter(get_object_vars($this)),
            array_flip(array_merge(['name', 'objectName', 'classObjectName'], $extraExclude))
        );
    }
}
