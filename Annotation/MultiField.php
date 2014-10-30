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
 * Annotation that can be used to define multi-field parameters.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
final class MultiField
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
     * Filters object values.
     *
     * @return array
     */
    public function filter()
    {
        return array_diff_key(
            array_filter(get_object_vars($this)),
            array_flip(['name'])
        );
    }
}
