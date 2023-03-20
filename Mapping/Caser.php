<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping;


use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

/**
 * Utility for string case transformations.
 */
class Caser
{
    /**
     * @var Inflector|null
     */
    private static $inflector;

    /**
     * Transforms string to camel case (e.g., resultString).
     *
     * @param string $string Text to transform.
     *
     * @return string
     */
    public static function camel($string)
    {
        if (!self::$inflector) {
            self::$inflector = InflectorFactory::create()->build();
        }

        return self::$inflector->camelize($string);
    }

    /**
     * Transforms string to snake case (e.g., result_string).
     *
     * @param string $string Text to transform.
     *
     * @return string
     */
    public static function snake($string)
    {
        $string = preg_replace('#([A-Z\d]+)([A-Z][a-z])#', '\1_\2', self::camel($string));
        $string = preg_replace('#([a-z\d])([A-Z])#', '\1_\2', $string);

        return strtolower(strtr($string, '-', '_'));
    }
}
