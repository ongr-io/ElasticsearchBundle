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

use Doctrine\Common\Inflector\Inflector;

/**
 * Transforms document properties from underscore case to camel case.
 */
abstract class AbstractAnnotationCamelizer
{
    /**
     * Camelizes properties when reading from document.
     *
     * @param array $values Key-value for properties to be defined in this class.
     */
    public function __construct(array $values)
    {
        foreach ($values as $key => $value) {
            $this->{Inflector::camelize($key)} = $value;
        }
    }

    /**
     * Converts string from camel into underscore case (port from rails).
     *
     * @param string $word
     *
     * @return string
     */
    protected function underscore($word)
    {
        $word = preg_replace('#([A-Z\d]+)([A-Z][a-z])#', '\1_\2', $word);
        $word = preg_replace('#([a-z\d])([A-Z])#', '\1_\2', $word);

        return strtolower(strtr($word, '-', '_'));
    }
}
