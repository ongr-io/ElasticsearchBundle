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
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Util\Inflector;
use ONGR\ElasticsearchBundle\Annotation\Document;
use ONGR\ElasticsearchBundle\Annotation\MultiField;
use ONGR\ElasticsearchBundle\Annotation\Property;
use ONGR\ElasticsearchBundle\Annotation\Suggester\AbstractSuggesterProperty;
use ONGR\ElasticsearchBundle\Annotation\Suggester\CompletionSuggesterProperty;
use ONGR\ElasticsearchBundle\Annotation\Suggester\ContextSuggesterProperty;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;

/**
 * Service for getting metadata from documents.
 */
class MetadataCollector
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
     * @var array
     */
    protected $bundles;

    /**
     * @var FileCacheReader
     */
    protected $reader;

    /**
     * Contains mappings gathered from bundle documents.
     *
     * @var array
     */
    private $documents = [];

    /**
     * Contains gathered objects which later adds to documents.
     *
     * @var array
     */
    private $objects = [];

    /**
     * Contains gathered aliases for object parameters.
     *
     * @var array
     */
    private $aliases = [];

    /**
     * Contains suggester object mapping to be used for getters and setters.
     *
     * @var array
     */
    private $suggesters = [];

    /**
     * Directory in bundle to load documents from.
     *
     * @var string
     */
    private $documentDir = 'Document';

    /**
     * @param array           $bundles Loaded bundles array.
     * @param FileCacheReader $reader  FileCacheReader.
     */
    public function __construct($bundles, $reader)
    {
        $this->reader = $reader;
        $this->bundles = $bundles;
        $this->registerAnnotations();
    }

    /**
     * Retrieves mapping from local cache otherwise runs through bundle files.
     *
     * @param string $bundle
     *
     * @return array
     */
    public function getMapping($bundle)
    {
        if (array_key_exists($bundle, $this->documents)) {
            return $this->documents[$bundle];
        }

        $mappings = $this->getBundleMapping($bundle);
        $filteredMappings = [];

        foreach ($mappings as $type => $mapping) {
            $filteredMappings[$type]['properties'] = $mapping['properties'];
            $mapping['fields']['_parent'] &&
                $filteredMappings[$type]['_parent'] = ['type' => $mapping['fields']['_parent']];
            $mapping['fields']['_ttl'] &&
                $filteredMappings[$type]['_ttl'] = $mapping['fields']['_ttl'];
        }

        $this->documents[$bundle] = $filteredMappings;

        return $this->documents[$bundle];
    }

    /**
     * Retrieves document mapping by namespace.
     *
     * @param string $namespace Document namespace.
     *
     * @return array|null
     */
    public function getMappingByNamespace($namespace)
    {
        return $this->getDocumentReflectionMapping(new \ReflectionClass($this->getNamespace($namespace)));
    }

    /**
     * Retrieves mapping from document.
     *
     * @param DocumentInterface $document
     *
     * @return array|null
     */
    public function getDocumentMapping(DocumentInterface $document)
    {
        return $this->getDocumentReflectionMapping(new \ReflectionObject($document));
    }

    /**
     * Searches for documents in bundle and tries to read them.
     *
     * Returns empty array on containing zero documents
     *
     * @param string $bundle
     *
     * @return array
     */
    public function getBundleMapping($bundle)
    {
        $mappings = [];

        $bundleReflection = new \ReflectionClass($this->getBundle($bundle));
        $documents = glob(
            dirname($bundleReflection->getFileName()) .
            DIRECTORY_SEPARATOR . $this->getDocumentDir() .
            DIRECTORY_SEPARATOR . '*.php'
        );

        // Loop through documents found in bundle.
        foreach ($documents as $document) {
            $documentReflection = new \ReflectionClass(
                $bundleReflection->getNamespaceName() .
                '\\' . str_replace('/', '\\', $this->getDocumentDir()) .
                '\\' . pathinfo($document, PATHINFO_FILENAME)
            );

            $documentMapping = $this->getDocumentReflectionMapping($documentReflection);
            if ($documentMapping !== null && !empty(reset($documentMapping)['properties'])) {
                $mappings = array_replace_recursive($mappings, $documentMapping);
            }
        }

        return $mappings;
    }

    /**
     * Document directory in bundle to load documents from.
     *
     * @param string $documentDir
     */
    public function setDocumentDir($documentDir)
    {
        $this->documentDir = $documentDir;
    }

    /**
     * Returns directory name in which documents should be put.
     *
     * @return string
     */
    public function getDocumentDir()
    {
        return $this->documentDir;
    }

    /**
     * Gathers annotation data from class.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array|null
     */
    private function getDocumentReflectionMapping(\ReflectionClass $reflectionClass)
    {
        /** @var Document $class */
        $class = $this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Document');
        if ($class !== null && $class->create) {
            $type = $this->getDocumentType($reflectionClass, $class);
            $parent = $class->parent === null ? $class->parent : $this->getDocumentParentType($class->parent);
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

            list($setters, $getters) = $this->getSettersAndGetters($reflectionClass, $properties);

            $hasLifecycleCallbacks = $this->reader->getClassAnnotation(
                $reflectionClass,
                'ONGR\ElasticsearchBundle\Annotation\HasLifecycleCallbacks'
            );

            $callbacks['lifecycleCallback'] = $hasLifecycleCallbacks ? $this->getCallbacks($reflectionClass) : [];

            $class = [
                $type => [
                    'properties' => $properties,
                    'callbacks' => $callbacks,
                    'setters' => $setters,
                    'getters' => $getters,
                    'fields' => [
                        '_parent' => $parent,
                        '_ttl' => $class->ttl,
                    ],
                    // Class info.
                    'namespace' => $reflectionClass->getNamespaceName() . '\\' . $reflectionClass->getShortName(),
                    'class' => $reflectionClass->getShortName(),
                ]
            ];
        }

        return $class;
    }

    /**
     * Returns information about accessing properties from document.
     * 
     * @param \ReflectionClass $reflectionClass Document reflection class.
     * @param array            $properties      Document properties.
     *
     * @return array
     */
    private function getSettersAndGetters(\ReflectionClass $reflectionClass, array $properties)
    {
        $setters = [];
        $getters = [];

        foreach ($properties as $property => $params) {
            if (array_key_exists($property, $this->aliases[$reflectionClass->getName()])) {
                list($setters[$property], $getters[$property]) = $this
                    ->getInfoAboutProperty(
                        $params,
                        $this->aliases[$reflectionClass->getName()][$property],
                        $reflectionClass
                    );
            } elseif ($reflectionClass->getParentClass() !== false) {
                list($parentSetters, $parentGetters) = $this
                    ->getSettersAndGetters($reflectionClass->getParentClass(), [$property => $params]);

                if ($parentSetters !== []) {
                    $setters = array_merge($setters, $parentSetters);
                }

                if ($parentGetters !== []) {
                    $getters = array_merge($getters, $parentGetters);
                }
            }
        }

        return [$setters, $getters];
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
     * @param array            $params          Property parameters.
     * @param string           $alias           Actual property name (not field name).
     * @param \ReflectionClass $reflectionClass Reflection class.
     *
     * @return array
     */
    private function getInfoAboutProperty($params, $alias, $reflectionClass)
    {
        $setter = $this->checkPropertyAccess($alias, 'set', $reflectionClass);
        $getter = $this->checkPropertyAccess($alias, 'get', $reflectionClass);

        if ($params['type'] === 'completion') {
            if (isset($this->suggesters[$reflectionClass->getName()][$alias])) {
                $suggestionObjectNamespace = $this->suggesters[$reflectionClass->getName()][$alias];
                $params['properties'] = $this->objects[$suggestionObjectNamespace]['properties'];
            }
        }

        if (isset($params['properties'])) {
            $data = $this->getInfoAboutPropertyObject($params['properties'], $alias, $reflectionClass);
            $setter['properties'] = $data['setter'];
            $getter['properties'] = $data['getter'];
            $getter['namespace'] = $data['namespace'];
            $setter['namespace'] = $data['namespace'];
            $getter['multiple'] = $data['multiple'];
            $setter['multiple'] = $data['multiple'];
        }

        return [
            $setter,
            $getter,
        ];
    }

    /**
     * Checks property access.
     *
     * @param string           $property
     * @param string           $methodPrefix
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     * 
     * @throws \LogicException
     */
    private function checkPropertyAccess($property, $methodPrefix, $reflectionClass)
    {
        $method = $methodPrefix . ucfirst(Inflector::classify($property));

        if ($reflectionClass->hasMethod($method)
            && $reflectionClass->getMethod($method)->isPublic()
        ) {
            return [
                'exec' => true,
                'name' => $reflectionClass->getMethod($method)->getName(),
            ];
        } elseif ($reflectionClass->hasProperty($property)
            && $reflectionClass->getProperty($property)->isPublic()
        ) {
            return [
                'exec' => false,
                'name' => $reflectionClass->getProperty($property)->getName(),
            ];
        } else {
            throw new \LogicException(
                sprintf('%ster for property "%s" can not be found.', ucfirst($methodPrefix), $property)
            );
        }
    }

    /**
     * Returns information about property object.
     *
     * @param array            $params          Parameters.
     * @param string           $propertyName    Property to be investigated name.
     * @param \ReflectionClass $reflectionClass Reflection class.
     *
     * @return array
     */
    private function getInfoAboutPropertyObject($params, $propertyName, $reflectionClass)
    {
        /** @var Property $type */
        $type = $this->getPropertyAnnotationData($reflectionClass->getProperty($propertyName));

        $childReflection = new \ReflectionClass($this->getNamespace($type->objectName));

        $setters = [];
        $getters = [];

        if (isset($this->aliases[$childReflection->getName()])) {
            foreach ($this->aliases[$childReflection->getName()] as $childField => $alias) {
                list($setters[$childField], $getters[$childField]) = $this
                    ->getInfoAboutProperty($params[$childField], $alias, $childReflection);
            }
        }

        return [
            'setter' => $setters,
            'getter' => $getters,
            'namespace' => $this->getNamespace($type->objectName),
            'multiple' => $type instanceof Property ? $type->multiple : false,
        ];
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
        $reflectionClass = new \ReflectionClass($this->getNamespace($namespace));

        if ($this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Object')
            || $this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Nested')
        ) {
            return ['properties' => $this->getProperties($reflectionClass)];
        }

        return null;
    }

    /**
     * Returns document parent.
     *
     * @param string $namespace
     *
     * @return string|null
     */
    private function getDocumentParentType($namespace)
    {
        $reflectionClass = new \ReflectionClass($this->getNamespace($namespace));

        /** @var Document $class */
        $class = $this->reader->getClassAnnotation($reflectionClass, 'ONGR\ElasticsearchBundle\Annotation\Document');

        return $class ? $this->getDocumentType($reflectionClass, $class) : null;
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
            $this->aliases[$reflectionClass->getName()][$type->name] = $property->getName();

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
     * Registers annotations to registry so that it could be used by reader.
     */
    private function registerAnnotations()
    {
        $annotations = [
            'Document',
            'Property',
            'Object',
            'HasLifecycleCallbacks',
            'Nested',
            'MultiField',
            'PrePersist',
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
     * Formats namespace from short syntax.
     *
     * @param string $namespace
     *
     * @return string
     */
    private function getNamespace($namespace)
    {
        if (strpos($namespace, ':') !== false) {
            list($bundle, $document) = explode(':', $namespace);
            $bundle = $this->getBundle($bundle);
            $namespace = substr($bundle, 0, strrpos($bundle, '\\')) . '\\' .
                $this->getDocumentDir() . '\\' . $document;
        }

        return $namespace;
    }

    /**
     * Returns bundle namesapce else throws an exception.
     *
     * @param string $name
     *
     * @return string
     * 
     * @throws \LogicException
     */
    private function getBundle($name)
    {
        if (array_key_exists($name, $this->bundles)) {
            return $this->bundles[$name];
        }

        throw new \LogicException(sprintf('Bundle \'%s\' does not exist.', $name));
    }

    /**
     * Returns property annotation data.
     *
     * @param \ReflectionProperty $property
     *
     * @return AbstractSuggesterProperty|Property
     */
    private function getPropertyAnnotationData($property)
    {
        $type = $this->reader->getPropertyAnnotation($property, self::PROPERTY_ANNOTATION);
        if ($type === null) {
            $type = $this->reader->getPropertyAnnotation($property, self::SUGGESTER_PROPERTY_ANNOTATION);
        }

        return $type;
    }

    /**
     * Gathers and returns annotated methods callbacks.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    public function getCallbacks(\ReflectionClass $reflectionClass)
    {
        $callbacks = [];

        foreach ($reflectionClass->getMethods() as $method) {
            $annotations = $this->reader->getMethodAnnotations($method);
            if (!empty($annotations)) {
                foreach ($annotations as $annotation) {
                    $callbacks[$annotation->type] = $method->getName();
                }
            }
        }

        return $callbacks;
    }
}
