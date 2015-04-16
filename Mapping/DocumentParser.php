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
use ONGR\ElasticsearchBundle\Annotation\Inherit;
use ONGR\ElasticsearchBundle\Annotation\MultiField;
use ONGR\ElasticsearchBundle\Annotation\Property;
use ONGR\ElasticsearchBundle\Annotation\Skip;
use ONGR\ElasticsearchBundle\Annotation\Suggester\AbstractSuggesterProperty;
use ONGR\ElasticsearchBundle\Mapping\Proxy\ProxyFactory;

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
     * @var Reader Used to read document annotations.
     */
    private $reader;

    /**
     * @var DocumentFinder Used to find documents.
     */
    private $finder;

    /**
     * @var array Contains gathered objects which later adds to documents.
     */
    private $objects = [];

    /**
     * @var array Document properties aliases.
     */
    private $aliases = [];

    /**
     * @var array Local cache for document properties.
     */
    private $properties = [];

    /**
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
     * Parses documents by used annotations and returns mapping for elasticsearch with some extra metadata.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array|null
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
                        '_parent' => $parent === null ? null : ['type' => $parent],
                        '_ttl' => $class->ttl,
                        'enabled' => $class->enabled,
                        '_all' => $class->all,
                    ],
                    'aliases' => $this->getAliases($reflectionClass),
                    'objects' => $this->getObjects(),
                    'proxyNamespace' => ProxyFactory::getProxyNamespace($reflectionClass, true),
                    'namespace' => $reflectionClass->getName(),
                    'class' => $reflectionClass->getShortName(),
                ],
            ];
        }

        return null;
    }

    /**
     * Returns property annotation data from reader.
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
     * Returns objects used in document.
     *
     * @return array
     */
    private function getObjects()
    {
        return array_keys($this->objects);
    }

    /**
     * Finds aliases for every property used in document including parent classes.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getAliases(\ReflectionClass $reflectionClass)
    {
        $reflectionName = $reflectionClass->getName();
        if (array_key_exists($reflectionName, $this->aliases)) {
            return $this->aliases[$reflectionName];
        }

        $alias = [];
        /** @var \ReflectionProperty $property */
        foreach ($this->getDocumentPropertiesReflection($reflectionClass) as $name => $property) {
            $type = $this->getPropertyAnnotationData($property);
            if ($type !== null) {
                $alias[$type->name] = [
                    'propertyName' => $name,
                    'type' => $type->type,
                ];
                if ($type->objectName) {
                    $child = new \ReflectionClass($this->finder->getNamespace($type->objectName));
                    $alias[$type->name] = array_merge(
                        $alias[$type->name],
                        [
                            'multiple' => $type instanceof Property ? $type->multiple : false,
                            'aliases' => $this->getAliases($child),
                            'proxyNamespace' => ProxyFactory::getProxyNamespace($child, true),
                            'namespace' => $child->getName(),
                        ]
                    );
                }
            }
        }

        $this->aliases[$reflectionName] = $alias;

        return $this->aliases[$reflectionName];
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
        return empty($document->type) ? $reflectionClass->getShortName() : $document->type;
    }

    /**
     * Returns all defined properties including private from parents.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getDocumentPropertiesReflection(\ReflectionClass $reflectionClass)
    {
        if (in_array($reflectionClass->getName(), $this->properties)) {
            return $this->properties[$reflectionClass->getName()];
        }

        $properties = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if (!in_array($property->getName(), $properties)) {
                $properties[$property->getName()] = $property;
            }
        }

        $parentReflection = $reflectionClass->getParentClass();
        if ($parentReflection !== false) {
            $properties = array_merge(
                $properties,
                array_diff_key($this->getDocumentPropertiesReflection($parentReflection), $properties)
            );
        }

        $this->properties[$reflectionClass->getName()] = $properties;

        return $properties;
    }

    /**
     * Returns properties of reflection class.
     *
     * @param \ReflectionClass $reflectionClass Class to read properties from.
     * @param array            $properties      Properties to skip.
     * @param bool             $flag            If false exludes properties, true only includes properties.
     *
     * @return array
     */
    private function getProperties(\ReflectionClass $reflectionClass, $properties = [], $flag = false)
    {
        $mapping = [];
        /** @var \ReflectionProperty $property */
        foreach ($this->getDocumentPropertiesReflection($reflectionClass) as $name => $property) {
            $type = $this->getPropertyAnnotationData($property);

            if ((in_array($name, $properties) && !$flag)
                || (!in_array($name, $properties) && $flag)
                || empty($type)
            ) {
                continue;
            }

            $maps = $type->dump();

            // Object.
            if (in_array($type->type, ['object', 'nested']) && !empty($type->objectName)) {
                $maps = array_replace_recursive($maps, $this->getObjectMapping($type->objectName));
            }

            // MultiField.
            if (isset($maps['fields']) && !in_array($type->type, ['object', 'nested'])) {
                $fieldsMap = [];
                /** @var MultiField $field */
                foreach ($maps['fields'] as $field) {
                    $fieldsMap[$field->name] = $field->dump();
                }
                $maps['fields'] = $fieldsMap;
            }

            // Suggestions.
            if ($type instanceof AbstractSuggesterProperty) {
                $this->getObjectMapping($type->objectName);
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
        $namespace = $this->finder->getNamespace($objectName);

        if (array_key_exists($namespace, $this->objects)) {
            return $this->objects[$namespace];
        }

        $this->objects[$namespace] = $this->getRelationMapping(new \ReflectionClass($namespace));

        return $this->objects[$namespace];
    }

    /**
     * Returns relation mapping by its reflection.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array|null
     */
    private function getRelationMapping(\ReflectionClass $reflectionClass)
    {
        if ($this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Object')
            || $this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Nested')
        ) {
            return ['properties' => $this->getProperties($reflectionClass)];
        }

        return null;
    }
}
