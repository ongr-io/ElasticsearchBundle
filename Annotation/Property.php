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
 * Annotation used to check mapping type during the parsing process.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Property
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
    public $index_analyzer;

    /**
     * @var string
     */
    public $search_analyzer;

    /**
     * @var float
     */
    public $boost;

    /**
     * @var bool
     */
    public $payloads;

    /**
     * @var array<\ONGR\ElasticsearchBundle\Annotation\MultiField>
     */
    public $fields;

    /**
     * Object name to map.
     *
     * @var string
     */
    public $objectName;

    /**
     * Filters object null values and name.
     *
     * @return array
     */
    public function filter()
    {
        return array_diff_key(
            array_filter(get_object_vars($this)),
            array_flip(['name', 'objectName'])
        );
    }
}
