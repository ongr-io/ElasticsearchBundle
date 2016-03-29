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
 * <description>
 *
 * @return <variableType>
 */
public function <methodName>()
{
<spaces>return $this-><fieldName>;
}';

    /**
     * @var string
     */
    private $setMethodTemplate =
        '/**
 * <description>
 *
 * @param <variableType> $<variableName>
 */
public function <methodName>($<variableName>)
{
<spaces>$this-><fieldName> = $<variableName>;
}';

    /**
     * @param array $metadata
     *
     * @return string
     */
    public function generateDocumentClass(array $metadata)
    {
        $lines[] = "<?php\n";
        $lines[] = sprintf('namespace %s;', substr($metadata['name'], 0, strrpos($metadata['name'], '\\'))) . "\n";
        $lines[] = "use ONGR\\ElasticsearchBundle\\Annotation as ES;\n";
        $lines[] = $this->generateDocumentDocBlock($metadata);
        $lines[] = 'class ' . $this->getClassName($metadata);
        $lines[] = "{";
        $lines[] = str_replace('<spaces>', $this->spaces, $this->generateDocumentBody($metadata));
        $lines[] = "}\n";

        return implode("\n", $lines);
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
            $lines[] = $this->spaces . 'private $' . $property['field_name'] . ";\n";
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
            $lines[] = $this->generateDocumentMethod($property, 'set') . "\n";
            $lines[] = $this->generateDocumentMethod($property, 'get') . "\n";
        }

        return implode("\n", $lines);
    }

    /**
     * Generates document method
     *
     * @param array  $metadata
     * @param string $type
     *
     * @return string
     */
    private function generateDocumentMethod(array $metadata, $type)
    {
        $replacements = [
            '<description>' => ucfirst($type) . ' ' . $metadata['field_name'],
            '<variableType>'      => $metadata['property_type'],
            '<variableName>'      => $metadata['field_name'],
            '<methodName>'        => $type . ucfirst($metadata['field_name']),
            '<fieldName>'         => $metadata['field_name'],
        ];

        return $this->prefixWithSpaces(
            str_replace(
                array_keys($replacements),
                array_values($replacements),
                $this->{$type . 'MethodTemplate'}
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
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var ' . $metadata['property_type'];

        $lines[] = $this->spaces . ' *';

        $column = [];
        if (isset($metadata['property_name']) && $metadata['property_name'] != $metadata['field_name']) {
            $column[] = 'name="' . $metadata['property_name'] . '"';
        }

        if (isset($metadata['property_type'])) {
            $column[] = 'type="' . $metadata['property_type'] . '"';
        }

        if (isset($metadata['property_options'])  && $metadata['property_options']) {
            $column[] = 'options={' . $metadata['property_options'] . '}';
        }

        $lines[] = $this->spaces . ' * @ES\\' . $metadata['annotation'] . '('
            . implode(', ', $column) . ')';

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
        $lines[] = '/**';
        $lines[] = ' * ' . $this->getClassName($metadata);

        $lines[] = ' *';

        $lines[] = sprintf(
            ' * @ES\%s(%s)',
            $metadata['annotation'],
            $metadata['type'] != lcfirst($this->getClassName($metadata))
                ? sprintf('type="%s"', $metadata['type']) : ''
        );

        $lines[] = ' */';

        return implode("\n", $lines);
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
}
