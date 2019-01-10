<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Generator;

use Doctrine\Common\Inflector\Inflector;

/**
 * Document Generator
 */
class DocumentGenerator
{
    /**
     * @var string
     */
    private $spaces = '    ';

    /**
     * @var string
     */
    private $getMethodTemplate =
        '/**
 * Returns <fieldName>
 *
 * @return string
 */
public function get<methodName>()
{
<spaces>return $this-><fieldName>;
}';

    /**
     * @var string
     */
    private $isMethodTemplate =
        '/**
 * Returns <fieldName>
 *
 * @return string
 */
public function is<methodName>()
{
<spaces>return $this-><fieldName>;
}';

    /**
     * @var string
     */
    private $setMethodTemplate =
        '/**
 * Sets <fieldName>
 *
 * @param string $<fieldName>
 *
 * @return self
 */
public function set<methodName>($<fieldName>)
{
<spaces>$this-><fieldName> = $<fieldName>;

<spaces>return $this;
}';

    /**
     * @var string
     */
    private $constructorTemplate =
        '/**
 * Constructor
 */
public function __construct()
{
<fields>
}';

    /**
     * @param array $metadata
     *
     * @return string
     */
    public function generateDocumentClass(array $metadata)
    {
        return implode(
            "\n",
            [
                "<?php\n",
                sprintf('namespace %s;', substr($metadata['name'], 0, strrpos($metadata['name'], '\\'))) . "\n",
                $this->generateDocumentUse($metadata),
                $this->generateDocumentDocBlock($metadata),
                'class ' . $this->getClassName($metadata),
                "{",
                str_replace('<spaces>', $this->spaces, $this->generateDocumentBody($metadata)),
                "}\n"
            ]
        );
    }

    /**
     * Generates document body
     *
     * @param array $metadata
     *
     * @return string
     */
    private function generateDocumentBody(array $metadata)
    {
        $lines = [];

        if ($properties = $this->generateDocumentProperties($metadata)) {
            $lines[] = $properties;
        }

        if ($this->hasMultipleEmbedded($metadata)) {
            $lines[] = $this->generateDocumentConstructor($metadata);
        }

        if ($methods = $this->generateDocumentMethods($metadata)) {
            $lines[] = $methods;
        }

        return rtrim(implode("\n", $lines));
    }

    /**
     * Generates document properties
     *
     * @param array $metadata
     *
     * @return string
     */
    private function generateDocumentProperties(array $metadata)
    {
        $lines = [];

        foreach ($metadata['properties'] as $property) {
            $lines[] = $this->generatePropertyDocBlock($property);
            $lines[] = $this->spaces . $property['visibility'] . ' $' . $property['field_name'] . ";\n";
        }

        return implode("\n", $lines);
    }

    /**
     * Generates document methods
     *
     * @param array $metadata
     *
     * @return string
     */
    private function generateDocumentMethods(array $metadata)
    {
        $lines = [];

        foreach ($metadata['properties'] as $property) {
            if (isset($property['visibility']) && $property['visibility'] === 'public') {
                continue;
            }
            $lines[] = $this->generateDocumentMethod($property, $this->setMethodTemplate) . "\n";
            if (isset($property['property_type']) && $property['property_type'] === 'boolean') {
                $lines[] = $this->generateDocumentMethod($property, $this->isMethodTemplate) . "\n";
            }

            $lines[] = $this->generateDocumentMethod($property, $this->getMethodTemplate) . "\n";
        }

        return implode("\n", $lines);
    }

    /**
     * Generates document constructor
     *
     * @param array $metadata
     *
     * @return string
     */
    private function generateDocumentConstructor(array $metadata)
    {
        $fields = [];

        foreach ($metadata['properties'] as $prop) {
            if ($prop['annotation'] == 'embedded' && isset($prop['property_multiple']) && $prop['property_multiple']) {
                $fields[] = sprintf('%s$this->%s = new ArrayCollection();', $this->spaces, $prop['field_name']);
            }
        }

        return $this->prefixWithSpaces(
            str_replace('<fields>', implode("\n", $fields), $this->constructorTemplate)
        ) . "\n";
    }

    /**
     * Generates document method
     *
     * @param array  $metadata
     * @param string $template
     *
     * @return string
     */
    private function generateDocumentMethod(array $metadata, $template)
    {
        return $this->prefixWithSpaces(
            str_replace(
                ['<methodName>', '<fieldName>'],
                [ucfirst($metadata['field_name']), $metadata['field_name']],
                $template
            )
        );
    }

    /**
     * Returns property doc block
     *
     * @param array $metadata
     *
     * @return string
     */
    private function generatePropertyDocBlock(array $metadata)
    {
        $lines = [
            $this->spaces . '/**',
            $this->spaces . ' * @var string',
            $this->spaces . ' *',
        ];

        $column = [];
        if (isset($metadata['property_name']) && $metadata['property_name'] != $metadata['field_name']) {
            $column[] = 'name="' . $metadata['property_name'] . '"';
        }

        if (isset($metadata['property_class'])) {
            $column[] = 'class="' . $metadata['property_class'] . '"';
        }

        if (isset($metadata['property_multiple']) && $metadata['property_multiple']) {
            $column[] = 'multiple=true';
        }

        if (isset($metadata['property_type']) && $metadata['annotation'] == 'property') {
            $column[] = 'type="' . $metadata['property_type'] . '"';
        }

        if (isset($metadata['property_default'])) {
            $column[] = 'default="' . $metadata['property_default'] . '"';
        }

        if (isset($metadata['property_options'])  && $metadata['property_options']) {
            $column[] = 'options={' . $metadata['property_options'] . '}';
        }

        $lines[] = $this->spaces . ' * @ES\\' . Inflector::classify($metadata['annotation'])
            . '(' . implode(', ', $column) . ')';

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    /**
     * Generates document doc block
     *
     * @param array $metadata
     *
     * @return string
     */
    private function generateDocumentDocBlock(array $metadata)
    {
        return str_replace(
            ['<className>', '<annotation>', '<options>'],
            [
                $this->getClassName($metadata),
                Inflector::classify($metadata['annotation']),
                $this->getAnnotationOptions($metadata),
            ],
            '/**
 * <className>
 *
 * @ES\<annotation>(<options>)
 */'
        );
    }

    /**
     * @param string $code
     *
     * @return string
     */
    private function prefixWithSpaces($code)
    {
        $lines = explode("\n", $code);

        foreach ($lines as $key => $value) {
            if ($value) {
                $lines[$key] = $this->spaces . $lines[$key];
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Returns class name
     *
     * @param array $metadata
     *
     * @return string
     */
    private function getClassName(array $metadata)
    {
        return ($pos = strrpos($metadata['name'], '\\'))
            ? substr($metadata['name'], $pos + 1, strlen($metadata['name'])) : $metadata['name'];
    }

    /**
     * Returns annotation options
     *
     * @param array $metadata
     *
     * @return string
     */
    private function getAnnotationOptions(array $metadata)
    {
        if (in_array($metadata['annotation'], ['object', 'object_type'])) {
            return '';
        }

        if ($metadata['type'] === Inflector::tableize($this->getClassName($metadata))) {
            return '';
        }

        return sprintf('type="%s"', $metadata['type']);
    }

    /**
     * Generates document use statements
     *
     * @param array $metadata
     *
     * @return string
     */
    private function generateDocumentUse(array $metadata)
    {
        $uses = ['use ONGR\ElasticsearchBundle\Annotation as ES;'];

        if ($this->hasMultipleEmbedded($metadata)) {
            $uses[] = 'use Doctrine\Common\Collections\ArrayCollection;';
        }

        return implode("\n", $uses) . "\n";
    }

    /**
     * @param array $metadata
     *
     * @return bool
     */
    private function hasMultipleEmbedded(array $metadata)
    {
        foreach ($metadata['properties'] as $prop) {
            if ($prop['annotation'] == 'embedded' && isset($prop['property_multiple']) && $prop['property_multiple']) {
                return true;
            }
        }

        return false;
    }
}
