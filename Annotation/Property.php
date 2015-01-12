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
 * Annotation used to check mapping type during the parsing process.
 *
 * @Annotation
 * @Target("PROPERTY")
 * @Attributes({
 *     @Attribute("index_analyzer", type = "string"),
 *     @Attribute("search_analyzer",  type = "string"),
 *     @Attribute("name", type = "string", required = true),
 *     @Attribute("type", type = "string", required = true),
 *     @Attribute("index", type = "string"),
 *     @Attribute("analyzer", type = "string"),
 *     @Attribute("boost", type = "float"),
 *     @Attribute("payloads", type = "bool"),
 *     @Attribute("fields", type = "array<\ONGR\ElasticsearchBundle\Annotation\MultiField>"),
 *     @Attribute("fielddata", type = "array"),
 *     @Attribute("objectName", type = "string"),
 *     @Attribute("multiple", type = "bool"),
 * })
 */
final class Property
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
        $this->objectName = isset($values['objectName']) ? $values['objectName'] : null;
        $this->multiple = isset($values['multiple']) ? $values['multiple'] : null;
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
     * @var string
     */
    public $objectName;

    /**
     * @var bool
     */
    public $multiple;

    /**
     * Filters object null values and name.
     *
     * @return array
     */
    public function filter()
    {
        return array_diff_key(
            array_filter($this->settings),
            array_flip(['name', 'objectName', 'multiple'])
        );
    }
}
