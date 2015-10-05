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

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Required;
use ONGR\ElasticsearchBundle\Mapping\Caser;

/**
 * Annotation used to check mapping type during the parsing process.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Property
{
    /**
     * Name of the type field.
     *
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * Field type.
     *
     * @var string
     *
     * @Required
     * @Enum({"string", "integer", "float", "date", "object", "nested", "multi_field", "geo_point", "geo_shape", "ip"})
     */
    public $type;

    /**
     * If type was selected as `object` or `nested` you need to define the object name.
     *
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

    /**
     * In this field you can define options (like analyzers and etc) for specific field types.
     *
     * @var array
     */
    public $options;

    /**
     * {@inheritdoc}
     */
    public function dump(array $exclude = [])
    {
        $array = array_diff_key(
            array_filter(
                get_object_vars($this),
                function ($value) {
                    return $value || is_bool($value);
                }
            ),
            array_flip(array_merge(['name', 'objectName', 'multiple'], $exclude))
        );

        return array_combine(
            array_map(
                function ($key) {
                    return Caser::snake($key);
                },
                array_keys($array)
            ),
            array_values($array)
        );
    }
}
