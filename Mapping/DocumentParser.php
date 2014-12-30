<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use ONGR\ElasticsearchBundle\Annotation\Document;
use ONGR\ElasticsearchBundle\Annotation\Suggester\AbstractSuggesterProperty;

/**
 * Document parser used for reading document annotations.
 */
class DocumentParser
{
    /**
     * @const string
     */
    const SUGGESTER_PROPERTY_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Suggester\AbstractSuggesterProperty';

    /**
     * @const string
     */
    const PROPERTY_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Property';

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var DocumentFinder
     */
    private $finder;

    /**
     * @var array Contains gathered objects which later adds to documents.
     */
    private $objects = [];

    /**
     * Constructor.
     *
     * @param Reader         $reader Used for reading annotations.
     * @param DocumentFinder $finder Used for resolving namespaces.
     */
    public function __construct(Reader $reader, DocumentFinder $finder)
    {
        $this->reader = $reader;
        $this->finder = $finder;
        $this->registerAnnotations();
    }

    /**
     * Reads document annotations.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    public function parse(\ReflectionClass $reflectionClass)
    {
        /** @var Document $class */
        $class = $this
            ->reader
            ->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Document');

        if ($class !== null && $class->create) {
            if ($class->parent !== null) {
                $parent = $this->getDocumentParentType(
                    new \ReflectionClass($this->finder->getNamespace($class->parent))
                );
            } else {
                $parent = null;
            }
            $type = $this->getDocumentType($reflectionClass, $class);
            $inherit = $this->getInheritedProperties($reflectionClass);

            $properties = $this->getProperties(
                $reflectionClass,
                array_merge($inherit, $this->getSkippedProperties($reflectionClass))
            );

            if (!empty($inherit)) {
                $properties = array_merge(
                    $properties,
                    $this->getProperties($reflectionClass->getParentClass(), $inherit, true)
                );
            }

            return [
                $type => [
                    'properties' => $properties,
                    'fields' => [
                        '_parent' => $parent,
                        '_ttl' => $class->ttl,
                    ],
                ],
            ];
        }

        return null;
    }

    /**
     * Returns property annotation data.
     *
     * @param \ReflectionProperty $property
     *
     * @return AbstractSuggesterProperty|Property
     */
    public function getPropertyAnnotationData($property)
    {
        $type = $this->reader->getPropertyAnnotation($property, self::PROPERTY_ANNOTATION);
        if ($type === null) {
            $type = $this->reader->getPropertyAnnotation($property, self::SUGGESTER_PROPERTY_ANNOTATION);
        }

        return $type;
    }

    /**
     * Registers annotations to registry so that it could be used by reader.
     */
    private function registerAnnotations()
    {
        $annotations = [
            'Document',
            'Property',
            'Object',
            'Nested',
            'MultiField',
            'Inherit',
            'Skip',
            'Suggester/CompletionSuggesterProperty',
            'Suggester/ContextSuggesterProperty',
            'Suggester/Context/CategoryContext',
            'Suggester/Context/GeoLocationContext',
        ];

        foreach ($annotations as $annotation) {
            AnnotationRegistry::registerFile(__DIR__ . "/../Annotation/{$annotation}.php");
        }
    }

    /**
     * Returns document parent.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return string|null
     */
    private function getDocumentParentType(\ReflectionClass $reflectionClass)
    {
        /** @var Document $class */
        $class = $this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Document');

        return $class ? $this->getDocumentType($reflectionClass, $class) : null;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getSkippedProperties(\ReflectionClass $reflectionClass)
    {
        /** @var Skip $class */
        $class = $this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Skip');

        return $class === null ? [] : $class->value;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getInheritedProperties(\ReflectionClass $reflectionClass)
    {
        /** @var Inherit $class */
        $class = $this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Inherit');

        return $class === null ? [] : $class->value;
    }

    /**
     * Returns document type.
     *
     * @param \ReflectionClass $reflectionClass
     * @param Document         $document
     *
     * @return string
     */
    private function getDocumentType(\ReflectionClass $reflectionClass, Document $document)
    {
        return strtolower(empty($document->type) ? $reflectionClass->getShortName() : $document->type);
    }

    /**
     * Returns properties of reflection class.
     *
     * @param \ReflectionClass $reflectionClass Class to read properties from.
     * @param array            $properties      Properties to skip.
     * @param array            $flag            If false exludes properties, true only includes properties.
     *
     * @return array
     */
    private function getProperties(\ReflectionClass $reflectionClass, $properties = [], $flag = false)
    {
        $mapping = [];
        /** @var \ReflectionProperty $property */
        foreach ($reflectionClass->getProperties() as $property) {
            $type = $this->getPropertyAnnotationData($property);

            if ((in_array($property->getName(), $properties) && !$flag)
                || (!in_array($property->getName(), $properties) && $flag)
                || empty($type)
            ) {
                continue;
            }

            $maps = $type->filter();

            // Object.
            if (in_array($type->type, ['object', 'nested']) && !empty($type->objectName)) {
                $maps = array_replace_recursive($maps, $this->getObjectMapping($type->objectName));
            }

            // MultiField.
            if (isset($maps['fields']) && !in_array($type->type, ['object', 'nested'])) {
                $fieldsMap = [];
                /** @var MultiField $field */
                foreach ($maps['fields'] as $field) {
                    $fieldsMap[$field->name] = $field->filter();
                }
                $maps['fields'] = $fieldsMap;
            }

            // Suggesters.
            if ($type instanceof AbstractSuggesterProperty) {
                $this->getObjectMapping($type->objectName);
                $this->suggesters[$reflectionClass->getName()][$property->getName()] = strtolower($type->objectName);
            }

            $mapping[$type->name] = $maps;
        }

        return $mapping;
    }

    /**
     * Returns object mapping.
     *
     * Loads from cache if it's already loaded.
     *
     * @param string $objectName
     *
     * @return array
     */
    private function getObjectMapping($objectName)
    {
        if (!empty($this->objects[strtolower($objectName)])) {
            $objMap = $this->objects[strtolower($objectName)];
        } else {
            $objMap = $this->getRelationMapping($objectName);
            $this->objects[strtolower($objectName)] = $objMap;
        }

        return $objMap;
    }

    /**
     * Returns relation mapping by its namespace.
     *
     * @param string $namespace
     *
     * @return array|null
     */
    private function getRelationMapping($namespace)
    {
        $reflectionClass = new \ReflectionClass($this->finder->getNamespace($namespace));

        if ($this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Object')
            || $this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Nested')
        ) {
            return ['properties' => $this->getProperties($reflectionClass)];
        }

        return null;
    }
}
