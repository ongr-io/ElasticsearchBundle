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
use ONGR\ElasticsearchBundle\Annotation\AbstractAnnotation;
use ONGR\ElasticsearchBundle\Annotation\Embedded;
use ONGR\ElasticsearchBundle\Annotation\HashMap;
use ONGR\ElasticsearchBundle\Annotation\Index;
use ONGR\ElasticsearchBundle\Annotation\NestedType;
use ONGR\ElasticsearchBundle\Annotation\ObjectType;
use ONGR\ElasticsearchBundle\Annotation\PropertiesAwareInterface;
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * Document parser used for reading document annotations.
 */
class DocumentParser
{
    /**
     * @var Reader Used to read document annotations.
     */
    private $reader;

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
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;

        #Fix for annotations loader until doctrine/annotations 2.0 will be released with the full autoload support.
        AnnotationRegistry::registerLoader('class_exists');
    }

    public function getIndexAliasName($namespace): string
    {
        $class = new \ReflectionClass($namespace);

        /** @var Index $document */
        $document = $this->reader->getClassAnnotation($class, Index::class);

        return $document->alias ?? Caser::snake($class->getShortName());
    }

    public function getIndexMetadata($namespace): array
    {
        $class = new \ReflectionClass($namespace);

        if ($class->isTrait()) {
            return [];
        }

        /** @var Index $document */
        $document = $this->reader->getClassAnnotation($class, Index::class);

        if ($document === null) {
            return [];
        }

        return [
            'settings' => $document->getSettings(),
            'mapping' => $this->getClassMetadata($class)
        ];
    }

    private function getClassMetadata(\ReflectionClass $reflectionClass): array
    {
        $mapping = [];

        /** @var \ReflectionProperty $property */
        foreach ($this->getDocumentPropertiesReflection($reflectionClass) as $name => $property) {
            $annotations = $this->reader->getPropertyAnnotations($property);

            /** @var AbstractAnnotation $annotation */
            foreach ($annotations as $annotation) {

                if (!$annotation instanceof PropertiesAwareInterface) {
                    continue;
                }

                $fieldMapping = $annotation->getSettings();

                if ($annotation instanceof Property) {
                    $fieldMapping['type'] = $annotation->type;
                }

                if ($annotation instanceof Embedded) {
                    $embeddedClass = new \ReflectionClass($annotation->class);
                    $fieldMapping['type'] = $this->getObjectMappingType($embeddedClass);
                    $fieldMapping['properties'] = $this->getClassMetadata($embeddedClass);
                }

                $mapping[$annotation->getName() ?? Caser::snake($name)] = $fieldMapping;
            }
        }

        return $mapping;
    }

    private function getObjectMappingType(\ReflectionClass $reflectionClass): string
    {
        switch (true) {
            case $this->reader->getClassAnnotation($reflectionClass, ObjectType::class):
                $type = ObjectType::TYPE;
                break;
            case $this->reader->getClassAnnotation($reflectionClass, NestedType::class):
                $type = NestedType::TYPE;
                break;
            default:
                throw new \LogicException(
                    sprintf(
                        '%s should have @ObjectType or @NestedType annotation to be used as embeddable object.',
                        $reflectionClass->getName()
                    )
                );
        }

        return $type;
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
            $type = $this->getPropertyAnnotationData($property);
            $type = $type !== null ? $type : $this->getEmbeddedAnnotationData($property);
            $type = $type !== null ? $type : $this->getHashMapAnnotationData($property);

            if ($type === null && $metaFields !== null
                && ($metaData = $this->getMetaFieldAnnotationData($property)) !== null) {
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
                    $child = new \ReflectionClass($type->class);
                    $alias[$type->name] = array_merge(
                        $alias[$type->name],
                        [
                            'type' => $this->getObjectMapping($type->class)['type'],
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

            $type = $this->getPropertyAnnotationData($property);
            $type = $type !== null ? $type : $this->getEmbeddedAnnotationData($property);

            if ($type instanceof Embedded) {
                $analyzers = array_merge(
                    $analyzers,
                    $this->getAnalyzers(new \ReflectionClass($type->class))
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
     * Returns object mapping.
     *
     * Loads from cache if it's already loaded.
     *
     * @param string $className
     *
     * @return array
     */
    private function getObjectMapping($namespace)
    {
        if (array_key_exists($namespace, $this->objects)) {
            return $this->objects[$namespace];
        }

        $reflectionClass = new \ReflectionClass($namespace);

        $documentAnnotation = $this->reader->getClassAnnotations($reflectionClass);

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
                        '%s should have @ObjectType or @NestedType annotation to be used as embeddable object.',
                        $namespace
                    )
                );
        }

        $this->objects[$namespace] = [
            'type' => $type,
            'properties' => $this->getClassProperties($reflectionClass),
        ];

        return $this->objects[$namespace];
    }
}
