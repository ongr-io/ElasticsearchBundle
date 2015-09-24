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
final class Property extends AbstractProperty
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

    /**
     * @var bool
     */
    public $includeInAll;

    /**
     * @var float
     */
    public $boost;

    /**
     * @var array<\ONGR\ElasticsearchBundle\Annotation\MultiField>
     */
    public $fields;

    /**
     * @var array
     */
    public $fieldData;

    /**
     * @var string Object name to map.
     */
    public $objectName;

    /**
     * Defines if related object will have one or multiple values.
     *
     * @var bool OneToOne or OneToMany.
     */
    public $multiple;

    /**
     * @var array
     */
    public $raw;
}
