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
use ONGR\ElasticsearchBundle\Annotation\HashMap;
use ONGR\ElasticsearchBundle\Annotation\MetaField;
use ONGR\ElasticsearchBundle\Annotation\Nested;
use ONGR\ElasticsearchBundle\Annotation\ParentDocument;
use ONGR\ElasticsearchBundle\Annotation\Property;
use ONGR\ElasticsearchBundle\Exception\MissingDocumentAnnotationException;

/**
 * Document parser used for reading document annotations.
 */
class DocumentParser
{
    const PROPERTY_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Property';
    const EMBEDDED_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Embedded';
    const DOCUMENT_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Document';
    const OBJECT_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\ObjectType';
    const NESTED_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Nested';

    // Meta fields
    const ID_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Id';
    const PARENT_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\ParentDocument';
    const ROUTING_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Routing';
    const VERSION_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\Version';
    const HASH_MAP_ANNOTATION = 'ONGR\ElasticsearchBundle\Annotation\HashMap';

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
     * @var array Local cache for documents
     */
    private $documents = [];

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
     * @param \ReflectionClass $class
     *
     * @return array|null
     * @throws MissingDocumentAnnotationException
     */
    public function parse(\ReflectionClass $class)
    {
        $className = $class->getName();

        if ($class->isTrait()) {
            return false;
        }

        if (!isset($this->documents[$className])) {
            /** @var Document $document */
            $document = $this->reader->getClassAnnotation($class, self::DOCUMENT_ANNOTATION);

            if ($document === null) {
                throw new MissingDocumentAnnotationException(
                    sprintf(
                        '"%s" class cannot be parsed as document because @Document annotation is missing.',
                        $class->getName()
                    )
                );
            }

            $fields = [];
            $aliases = $this->getAliases($class, $fields);

            $this->documents[$className] = [
                'type' => $document->type ?: Caser::snake($class->getShortName()),
                'properties' => $this->getProperties($class),
                'fields' => array_filter(
                    array_merge(
                        $document->dump(),
                        $fields
                    )
                ),
                'aliases' => $aliases,
                'analyzers' => $this->getAnalyzers($class),
                'objects' => $this->getObjects(),
                'namespace' => $class->getName(),
                'class' => $class->getShortName(),
            ];
        }
        return $this->documents[$className];
    }

    /**
     * Returns document annotation data from reader.
     *
     * @param \ReflectionClass $document
     *
     * @return Document|object|null
     */
    private function getDocumentAnnotationData($document)
    {
        return $this->reader->getClassAnnotation($document, self::DOCUMENT_ANNOTATION);
    }

    /**
     * Returns property annotation data from reader.
     *
     * @param \ReflectionProperty $property
     *
     * @return Property|object|null
     */
    private function getPropertyAnnotationData(\ReflectionProperty $property)
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
     * @return Embedded|object|null
     */
    private function getEmbeddedAnnotationData(\ReflectionProperty $property)
    {
        $result = $this->reader->getPropertyAnnotation($property, self::EMBEDDED_ANNOTATION);

        if ($result !== null && $result->name === null) {
            $result->name = Caser::snake($property->getName());
        }

        return $result;
    }

    /**
     * Returns HashMap annotation data from reader.
     *
     * @param \ReflectionProperty $property
     *
     * @return HashMap|object|null
     */
    private function getHashMapAnnotationData(\ReflectionProperty $property)
    {
        $result = $this->reader->getPropertyAnnotation($property, self::HASH_MAP_ANNOTATION);

        if ($result !== null && $result->name === null) {
            $result->name = Caser::snake($property->getName());
        }

        return $result;
    }

    /**
     * Returns meta field annotation data from reader.
     *
     * @param \ReflectionProperty $property
     * @param string              $directory The name of the Document directory in the bundle
     *
     * @return array
     */
    private function getMetaFieldAnnotationData($property, $directory)
    {
        /** @var MetaField $annotation */
        $annotation = $this->reader->getPropertyAnnotation($property, self::ID_ANNOTATION);
        $annotation = $annotation ?: $this->reader->getPropertyAnnotation($property, self::PARENT_ANNOTATION);
        $annotation = $annotation ?: $this->reader->getPropertyAnnotation($property, self::ROUTING_ANNOTATION);
        $annotation = $annotation ?: $this->reader->getPropertyAnnotation($property, self::VERSION_ANNOTATION);

        if ($annotation === null) {
            return null;
        }

        $data = [
            'name' => $annotation->getName(),
            'settings' => $annotation->getSettings(),
        ];

        if ($annotation instanceof ParentDocument) {
            $data['settings']['type'] = $this->getDocumentType($annotation->class, $directory);
        }

        return $data;
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
     * @param array            $metaFields
     *
     * @return array
     */
    private function getAliases(\ReflectionClass $reflectionClass, array &$metaFields = null)
    {
        $reflectionName = $reflectionClass->getName();

        // We skip cache in case $metaFields is given. This should not affect performance
        // because for each document this method is called only once. For objects it might
        // be called few times.
        if ($metaFields === null && array_key_exists($reflectionName, $this->aliases)) {
            return $this->aliases[$reflectionName];
        }

        $alias = [];

        /** @var \ReflectionProperty[] $properties */
        $properties = $this->getDocumentPropertiesReflection($reflectionClass);

        foreach ($properties as $name => $property) {
            $directory = $this->guessDirName($property->getDeclaringClass());

            $type = $this->getPropertyAnnotationData($property);
            $type = $type !== null ? $type : $this->getEmbeddedAnnotationData($property);
            $type = $type !== null ? $type : $this->getHashMapAnnotationData($property);

            if ($type === null && $metaFields !== null
                && ($metaData = $this->getMetaFieldAnnotationData($property, $directory)) !== null) {
                $metaFields[$metaData['name']] = $metaData['settings'];
                $type = new \stdClass();
                $type->name = $metaData['name'];
            }
            if ($type !== null) {
                $alias[$type->name] = [
                    'propertyName' => $name,
                ];

                if ($type instanceof Property) {
                    $alias[$type->name]['type'] = $type->type;
                }

                if ($type instanceof HashMap) {
                    $alias[$type->name]['type'] = HashMap::NAME;
                }

                $alias[$type->name][HashMap::NAME] = $type instanceof HashMap;

                switch (true) {
                    case $property->isPublic():
                        $propertyType = 'public';
                        break;
                    case $property->isProtected():
                    case $property->isPrivate():
                        $propertyType = 'private';
                        $alias[$type->name]['methods'] = $this->getMutatorMethods(
                            $reflectionClass,
                            $name,
                            $type instanceof Property ? $type->type : null
                        );
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
                    $child = new \ReflectionClass($this->finder->getNamespace($type->class, $directory));
                    $alias[$type->name] = array_merge(
                        $alias[$type->name],
                        [
                            'type' => $this->getObjectMapping($type->class, $directory)['type'],
                            'multiple' => $type->multiple,
                            'aliases' => $this->getAliases($child, $metaFields),
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
            'Property',
            'Embedded',
            'ObjectType',
            'Nested',
            'Id',
            'ParentDocument',
            'Routing',
            'Version',
            'HashMap',
        ];

        foreach ($annotations as $annotation) {
            AnnotationRegistry::registerFile(__DIR__ . "/../Annotation/{$annotation}.php");
        }

        if (version_compare(PHP_VERSION, '7.2.0') < 0) {
            AnnotationRegistry::registerFile(__DIR__ . "/../Annotation/Object.php");
        }
    }

    /**
     * Returns document type.
     *
     * @param string $document  Format must be like AcmeBundle:Document.
     * @param string $directory The Document directory name of the bundle.
     *
     * @return string
     */
    private function getDocumentType($document, $directory)
    {
        $namespace = $this->finder->getNamespace($document, $directory);
        $reflectionClass = new \ReflectionClass($namespace);
        $document = $this->getDocumentAnnotationData($reflectionClass);

        return empty($document->type) ? Caser::snake($reflectionClass->getShortName()) : $document->type;
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
     * Parses analyzers list from document mapping.
     *
     * @param \ReflectionClass $reflectionClass
     * @return array
     */
    private function getAnalyzers(\ReflectionClass $reflectionClass)
    {
        $analyzers = [];

        foreach ($this->getDocumentPropertiesReflection($reflectionClass) as $name => $property) {
            $directory = $this->guessDirName($property->getDeclaringClass());

            $type = $this->getPropertyAnnotationData($property);
            $type = $type !== null ? $type : $this->getEmbeddedAnnotationData($property);

            if ($type instanceof Embedded) {
                $analyzers = array_merge(
                    $analyzers,
                    $this->getAnalyzers(new \ReflectionClass($this->finder->getNamespace($type->class, $directory)))
                );
            }

            if ($type instanceof Property) {
                if (isset($type->options['analyzer'])) {
                    $analyzers[] = $type->options['analyzer'];
                }
                if (isset($type->options['search_analyzer'])) {
                    $analyzers[] = $type->options['search_analyzer'];
                }

                if (isset($type->options['fields'])) {
                    foreach ($type->options['fields'] as $field) {
                        if (isset($field['analyzer'])) {
                            $analyzers[] = $field['analyzer'];
                        }
                        if (isset($field['search_analyzer'])) {
                            $analyzers[] = $field['search_analyzer'];
                        }
                    }
                }
            }
        }
        return array_unique($analyzers);
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
            $directory = $this->guessDirName($property->getDeclaringClass());

            $type = $this->getPropertyAnnotationData($property);
            $type = $type !== null ? $type : $this->getEmbeddedAnnotationData($property);
            $type = $type !== null ? $type : $this->getHashMapAnnotationData($property);

            if ((in_array($name, $properties) && !$flag)
                || (!in_array($name, $properties) && $flag)
                || empty($type)
            ) {
                continue;
            }

            $map = $type->dump();

            // Inner object
            if ($type instanceof Embedded) {
                $map = array_replace_recursive($map, $this->getObjectMapping($type->class, $directory));
            }

            // HashMap object
            if ($type instanceof HashMap) {
                $map = array_replace_recursive($map, [
                    'type' => Nested::NAME,
                    'dynamic' => true,
                ]);
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
     * @param string $directory Name of the directory where the Document is
     *
     * @return array
     */
    private function getObjectMapping($className, $directory)
    {
        $namespace = $this->finder->getNamespace($className, $directory);

        if (array_key_exists($namespace, $this->objects)) {
            return $this->objects[$namespace];
        }

        $reflectionClass = new \ReflectionClass($namespace);

        switch (true) {
            case $this->reader->getClassAnnotation($reflectionClass, self::OBJECT_ANNOTATION):
                $type = 'object';
                break;
            case $this->reader->getClassAnnotation($reflectionClass, self::NESTED_ANNOTATION):
                $type = 'nested';
                break;
            default:
                throw new \LogicException(
                    sprintf(
                        '%s should have @ObjectType or @Nested annotation to be used as embeddable object.',
                        $className
                    )
                );
        }

        $this->objects[$namespace] = [
            'type' => $type,
            'properties' => $this->getProperties($reflectionClass),
        ];

        return $this->objects[$namespace];
    }

    /**
     * @param \ReflectionClass $reflection
     *
     * @return string
     */
    private function guessDirName(\ReflectionClass $reflection)
    {
        return substr(
            $directory = $reflection->getName(),
            $start = strpos($directory, '\\') + 1,
            strrpos($directory, '\\') - $start
        );
    }
}
