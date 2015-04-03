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

use ONGR\ElasticsearchBundle\Mapping\AbstractAnnotationCamelizer;
use ONGR\ElasticsearchBundle\Mapping\DumperInterface;

/**
 * Makes sure thats annotations are well formated.
 */
abstract class AbstractProperty extends AbstractAnnotationCamelizer implements DumperInterface
{
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
                    return $this->underscore($key);
                },
                array_keys($array)
            ),
            array_values($array)
        );
    }
}
