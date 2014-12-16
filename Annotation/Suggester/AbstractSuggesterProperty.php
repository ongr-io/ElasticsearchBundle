<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation\Suggester;

/**
 * Abstract class for various suggester annotations.
 *
 * @Attributes({
 *     @Attribute("name", type = "string", required = true),
 *     @Attribute("type", type = "string", required = true),
 *     @Attribute("index_analyzer", type = "string"),
 *     @Attribute("search_analyzer",  type = "string"),
 *     @Attribute("preserve_separators",  type = "int"),
 *     @Attribute("preserve_position_increments",  type = "bool"),
 *     @Attribute("max_input_length",  type = "int"),
 *     @Attribute("objectName", type = "string", required = true),
 * })
 */
abstract class AbstractSuggesterProperty
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
        $this->settings = $values;
        $this->name = $values['name'];
        $this->objectName = $values['objectName'];
        $this->settings['type'] = $this->type;
    }

    /**
     * @var string
     */
    public $type = 'completion';

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $objectName;

    /**
     * Returns required properties.
     *
     * @param array $extraExclude Extra object variables to exclude.
     *
     * @return array
     */
    public function filter($extraExclude = [])
    {
        return array_diff_key(
            array_filter($this->settings),
            array_flip(array_merge(['name', 'objectName', 'classObjectName'], $extraExclude))
        );
    }
}
