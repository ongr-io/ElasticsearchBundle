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
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * Document parser used for reading document annotations.
 */
class DocumentParser
{
    /**
     * @const string
     */
    const PROPERTY_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Property';

    /**
     * @const string
     */
    const DOCUMENT_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Document';

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
        return $this->reader->getPropertyAnnotation($property, self::PROPERTY_ANNOTATION);
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
            if ($type !== null) {
                $alias[$type->name] = [
                    'propertyName' => $name,
                    'type' => $type->type,
                ];
                switch (true) {
                    case $property->isPublic():
                        $propertyType = 'public';
                        break;
                    case $property->isProtected():
                    case $property->isPrivate():
                        $propertyType = 'private';

                        $camelCaseName = ucfirst(Caser::camel($name));
                        if ($reflectionClass->hasMethod('get'.$camelCaseName)
                            && $reflectionClass->hasMethod('set'.$camelCaseName)
                        ) {
                            $alias[$type->name]['methods'] = [
                                'getter' => 'get'.$camelCaseName,
                                'setter' => 'set'.$camelCaseName,
                            ];
                        } else {
                            $message = sprintf(
                                'Missing %s() method in %s class. Add it, or change property to public.',
                                $name,
                                $reflectionName
                            );
                            throw new \LogicException($message);
                        }
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


                if ($type->objectName) {
                    $child = new \ReflectionClass($this->finder->getNamespace($type->objectName));
                    $alias[$type->name] = array_merge(
                        $alias[$type->name],
                        [
                            'multiple' => $type instanceof Property ? $type->multiple : false,
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
     * Registers annotations to registry so that it could be used by reader.
     */
    private function registerAnnotations()
    {
        $annotations = [
            'Document',
            'Property',
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

            if ((in_array($name, $properties) && !$flag)
                || (!in_array($name, $properties) && $flag)
                || empty($type)
            ) {
                continue;
            }

            $map = $type->dump();

            // Object.
            if (in_array($type->type, ['object', 'nested']) && !empty($type->objectName)) {
                $map = array_replace_recursive($map, $this->getObjectMapping($type->objectName));
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
