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
use ONGR\ElasticsearchBundle\Annotation\Embedded;
use ONGR\ElasticsearchBundle\Annotation\MetaField;
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * Document parser used for reading document annotations.
 */
class DocumentParser
{
    const META_FIELD_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\MetaField';
    const PROPERTY_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Property';
    const EMBEDDED_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Embedded';
    const DOCUMENT_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Document';
    const OBJECT_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Object';
    const NESTED_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Nested';

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
     * @param \ReflectionClass $document
     *
     * @return array
     */
    public function parse(\ReflectionClass $document)
    {
        /** @var Document $class */
        $class = $this
            ->reader
            ->getClassAnnotation($document, self::DOCUMENT_ANNOTATION);

        if ($class !== null) {
            if ($class->parent !== null) {
                $parent = $this->getDocumentType($class->parent);
            } else {
                $parent = null;
            }

            $properties = $this->getProperties($document);

            if ($class->type) {
                $documentType = $class->type;
            } else {
                $documentType = $document->getShortName();
            }

            return [
                'type' => $documentType,
                'properties' => $properties,
                'fields' => array_filter(
                    array_merge(
                        $class->dump(),
                        ['_parent' => $parent === null ? null : ['type' => $parent]]
                    )
                ),
                'aliases' => $this->getAliases($document),
                'objects' => $this->getObjects(),
                'namespace' => $document->getName(),
                'class' => $document->getShortName(),
            ];
        }

        return [];
    }

    /**
     * Returns document annotation data from reader.
     *
     * @param \ReflectionClass $document
     *
     * @return Document|null
     */
    public function getDocumentAnnotationData($document)
    {
        return $this->reader->getClassAnnotation($document, self::DOCUMENT_ANNOTATION);
    }

    /**
     * Returns property annotation data from reader.
     *
     * @param \ReflectionProperty $property
     *
     * @return Property|null
     */
    public function getPropertyAnnotationData($property)
    {
        $result = $this->reader->getPropertyAnnotation($property, self::PROPERTY_ANNOTATION);

        if ($result !== null && $result->name === null) {
            $result->name = Caser::snake($property->getName());
        }

        return $result;
    }

    /**
     * Returns Embedded annotation data from reader.
     *
     * @param \ReflectionProperty $property
     *
     * @return Embedded|null
     */
    public function getEmbeddedAnnotationData($property)
    {
        $result = $this->reader->getPropertyAnnotation($property, self::EMBEDDED_ANNOTATION);

        if ($result !== null && $result->name === null) {
            $result->name = Caser::snake($property->getName());
        }

        return $result;
    }

    /**
     * Returns meta field annotation data from reader.
     *
     * @param \ReflectionProperty $property
     *
     * @return MetaField|null
     */
    public function getMetaFieldAnnotationData($property)
    {
        return $this->reader->getPropertyAnnotation($property, self::META_FIELD_ANNOTATION);
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

        /** @var \ReflectionProperty[] $properties */
        $properties = $this->getDocumentPropertiesReflection($reflectionClass);

        foreach ($properties as $name => $property) {
            $type = $this->getPropertyAnnotationData($property);
            $type = $type !== null ? $type : $this->getEmbeddedAnnotationData($property);
            $type = $type !== null ? $type : $this->getMetaFieldAnnotationData($property);
            if ($type !== null) {
                $alias[$type->name] = [
                    'propertyName' => $name,
                ];

                if ($type instanceof Property) {
                    $alias[$type->name]['type'] = $type->type;
                }

                switch (true) {
                    case $property->isPublic():
                        $propertyType = 'public';
                        break;
                    case $property->isProtected():
                    case $property->isPrivate():
                        $propertyType = 'private';
                        $alias[$type->name]['methods'] = $this->getMutatorMethods($reflectionClass, $name, $type->type);
                        break;
                    default:
                        $message = sprintf(
                            'Wrong property %s type of %s class types cannot '.
                            'be static or abstract.',
                            $name,
                            $reflectionName
                        );
                        throw new \LogicException($message);
                }
                $alias[$type->name]['propertyType'] = $propertyType;

                if ($type instanceof Embedded) {
                    $child = new \ReflectionClass($this->finder->getNamespace($type->class));
                    $alias[$type->name] = array_merge(
                        $alias[$type->name],
                        [
                            'type' => $this->getInnerObjectType($type->class),
                            'multiple' => $type->multiple,
                            'aliases' => $this->getAliases($child),
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
     * Checks if class have setter and getter, and returns them in array.
     *
     * @param \ReflectionClass $reflectionClass
     * @param string           $property
     *
     * @return array
     */
    private function getMutatorMethods(\ReflectionClass $reflectionClass, $property, $propertyType)
    {
        $camelCaseName = ucfirst(Caser::camel($property));
        $setterName = 'set'.$camelCaseName;
        if (!$reflectionClass->hasMethod($setterName)) {
            $message = sprintf(
                'Missing %s() method in %s class. Add it, or change property to public.',
                $setterName,
                $reflectionClass->getName()
            );
            throw new \LogicException($message);
        }

        if ($reflectionClass->hasMethod('get'.$camelCaseName)) {
            return [
                'getter' => 'get' . $camelCaseName,
                'setter' => $setterName
            ];
        }

        if ($propertyType === 'boolean') {
            if ($reflectionClass->hasMethod('is' . $camelCaseName)) {
                return [
                    'getter' => 'is' . $camelCaseName,
                    'setter' => $setterName
                ];
            }

            $message = sprintf(
                'Missing %s() or %s() method in %s class. Add it, or change property to public.',
                'get'.$camelCaseName,
                'is'.$camelCaseName,
                $reflectionClass->getName()
            );
            throw new \LogicException($message);
        }

        $message = sprintf(
            'Missing %s() method in %s class. Add it, or change property to public.',
            'get'.$camelCaseName,
            $reflectionClass->getName()
        );
        throw new \LogicException($message);
    }

    /**
     * Registers annotations to registry so that it could be used by reader.
     */
    private function registerAnnotations()
    {
        $annotations = [
            'Document',
            'MetaField',
            'Property',
            'Embedded',
            'Object',
            'Nested',
        ];

        foreach ($annotations as $annotation) {
            AnnotationRegistry::registerFile(__DIR__ . "/../Annotation/{$annotation}.php");
        }
    }

    /**
     * Returns document type.
     *
     * @param string $document Format must be like AcmeBundle:Document.
     *
     * @return string
     */
    private function getDocumentType($document)
    {
        $namespace = $this->finder->getNamespace($document);
        $reflectionClass = new \ReflectionClass($namespace);
        $document = $this->getDocumentAnnotationData($reflectionClass);

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
            $type = $type !== null ? $type : $this->getEmbeddedAnnotationData($property);

            if ((in_array($name, $properties) && !$flag)
                || (!in_array($name, $properties) && $flag)
                || empty($type)
            ) {
                continue;
            }

            $map = $type->dump();

            // Inner object
            if ($type instanceof Embedded) {
                $map['type'] = $this->getInnerObjectType($type->class);
                $map = array_replace_recursive($map, $this->getObjectMapping($type->class));
            }

            // If there is set some Raw options, it will override current ones.
            if (isset($map['options'])) {
                $options = $map['options'];
                unset($map['options']);
                $map = array_merge($map, $options);
            }

            $mapping[$type->name] = $map;
        }

        return $mapping;
    }

    /**
     * Returns object mapping.
     *
     * Loads from cache if it's already loaded.
     *
     * @param string $className
     *
     * @return array
     */
    private function getObjectMapping($className)
    {
        $namespace = $this->finder->getNamespace($className);

        if (array_key_exists($namespace, $this->objects)) {
            return $this->objects[$namespace];
        }

        $this->objects[$namespace] = $this->getRelationMapping(new \ReflectionClass($namespace));

        if ($this->objects[$namespace] === null) {
            throw new \LogicException(
                sprintf('%s should have @Object or @Nested annotation to be used as as object property.', $objectName)
            );
        }

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
        if ($this->reader->getClassAnnotation($reflectionClass, self::OBJECT_ANNOTATION)
            || $this->reader->getClassAnnotation($reflectionClass, self::NESTED_ANNOTATION)
        ) {
            return ['properties' => $this->getProperties($reflectionClass)];
        }

        return null;
    }

    /**
     * Returns inner object type by it's class name.
     *
     * @param string $className
     *
     * @return null|string
     */
    private function getInnerObjectType($className)
    {
        $reflection = new \ReflectionClass($this->finder->getNamespace($className));

        if ($this->reader->getClassAnnotation($reflection, self::OBJECT_ANNOTATION)) {
            return 'object';
        }

        if ($this->reader->getClassAnnotation($reflection, self::NESTED_ANNOTATION)) {
            return 'nested';
        }

        return null;
    }
}
