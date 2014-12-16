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
 * @Attributes({
 *     @Attribute("index_analyzer", type = "string"),
 *     @Attribute("search_analyzer",  type = "string"),
 *     @Attribute("name", type = "string", required = true),
 *     @Attribute("type", type = "string", required = true),
 *     @Attribute("index", type = "string"),
 *     @Attribute("analyzer", type = "string"),
 * })
 */
final class MultiField
{
    /**
     * @var array
     */
    private $settings;

    /**
     * Constructor for lowercase settings.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->type = $values['type'];
        $this->name = $values['name'];
        $this->settings = $values;
    }

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * Filters object values.
     *
     * @return array
     */
    public function filter()
    {
        return array_diff_key(
            array_filter($this->settings),
            array_flip(['name'])
        );
    }
}
