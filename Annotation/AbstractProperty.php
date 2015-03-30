<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Annotation;

use Ongr\ElasticsearchBundle\Mapping\AbstractAnnotationCamelizer;
use Ongr\ElasticsearchBundle\Mapping\DumperInterface;

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
            array_filter(get_object_vars($this)),
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
