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

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\EntityGenerator;

/**
 * Document Generator
 */
class DocumentGenerator extends EntityGenerator
{
    /**
     * @var string
     */
    private $documentType;

    /**
     * @var string
     */
    protected static $setMethodTemplate =
        '/**
 * <description>
 *
 * @param <variableType> $<variableName>
 */
public function <methodName>(<methodTypeHint>$<variableName><variableDefault>)
{
<spaces>$this-><fieldName> = $<variableName>;
}';

    /**
     * {@inheritdoc}
     */
    protected function generateEntityUse()
    {
        return $this->generateAnnotations ? ("\n" . 'use ONGR\ElasticsearchBundle\Annotation as ES;' . "\n") : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function generateEntityDocBlock(ClassMetadataInfo $metadata)
    {
        $lines = [];
        $lines[] = '/**';
        $lines[] = ' * ' . $this->getClassName($metadata);

        if ($this->generateAnnotations) {
            $lines[] = ' *';

            $lines[] = sprintf(
                ' * @%sDocument(%s)',
                $this->annotationsPrefix,
                $this->documentType != lcfirst($this->getClassName($metadata))
                    ? sprintf('type="%s"', $this->documentType) : ''
            );
        }

        $lines[] = ' */';

        return implode("\n", $lines);
    }

    /**
     * {@inheritdoc}
     */
    protected function generateFieldMappingPropertyDocBlock(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines = [];
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var ' . $this->getType($fieldMapping['type']);

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            $column = [];
            if (isset($fieldMapping['property_name']) && $fieldMapping['property_name'] != $fieldMapping['fieldName']) {
                $column[] = 'name="' . $fieldMapping['property_name'] . '"';
            }

            if (isset($fieldMapping['property_type'])) {
                $column[] = 'type="' . $fieldMapping['property_type'] . '"';
            }

            if (isset($fieldMapping['property_options'])  && $fieldMapping['property_options']) {
                $column[] = 'options={' . $fieldMapping['property_options'] . '}';
            }

            $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . $fieldMapping['annotation'] . '('
                . implode(', ', $column) . ')';
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    /**
     * Sets document type
     *
     * @param string $documentType
     *
     * @return DocumentGenerator
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;

        return $this;
    }
}
