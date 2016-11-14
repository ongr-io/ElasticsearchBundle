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

use ONGR\ElasticsearchBundle\Mapping\DumperInterface;

/**
 * Annotation to mark a class as an Elasticsearch document.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Document implements DumperInterface
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var bool
     *
     * @deprecated Use `options` to pass parameters instead.
     */
    public $enabled;

    /**
     * @var array
     *
     * @deprecated Use `options` to pass parameters instead.
     */
    public $all;

    /**
     * @var string
     *
     * @deprecated Use `options` to pass parameters instead.
     */
    public $dynamic;

    /**
     * @var array
     *
     * @deprecated Use `options` to pass parameters instead.
     */
    public $dynamicTemplates;
    
    /**
     * @var array
     *
     * @deprecated Use `options` to pass parameters instead.
     */
    public $transform;

    /**
     * @var array
     *
     * @deprecated Use `options` to pass parameters instead.
     */
    public $dynamicDateFormats;

    /**
     * @var array
     */
    public $options = [];

    /**
     * {@inheritdoc}
     */
    public function dump(array $exclude = [])
    {
        return array_diff_key(
            array_merge([
                'enabled' => $this->enabled,
                '_all' => $this->all,
                'dynamic' => $this->dynamic,
                'dynamic_templates' => $this->dynamicTemplates,
                'transform' => $this->transform,
                'dynamic_date_formats' => $this->dynamicDateFormats,
            ], $this->options),
            $exclude
        );
    }
}
