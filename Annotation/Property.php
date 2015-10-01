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
use Doctrine\Common\Annotations\Annotation\Enum;

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
     * @Enum('string', 'integer', 'float', 'date', 'object', 'nested', 'multi_field', 'geo_point', 'geo_shape', 'ip')
     */
    public $type;

    /**
     * @var array<\ONGR\ElasticsearchBundle\Annotation\MultiField>
     */
    public $fields;

    /**
     * @var string Object name to map.
     */
    public $objectName;

    /**
     * Defines if related object will have one or multiple values.
     * If this value is set to true, in the result ObjectIterator will be provided,
     * otherwise you will get Document object
     *
     * @var bool DocumentInterface or ObjectIterator.
     */
    public $multiple;
}
