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
use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Mapping\Proxy\ProxyLoader;

/**
 * DocumentParser wrapper for getting bundle documents mapping.
 */
class MetadataCollector
{
    /**
     * @var DocumentFinder
     */
    private $finder;

    /**
     * @var DocumentParser
     */
    private $parser;

    /**
     * @var array Contains mappings gathered from bundle documents.
     */
    private $documents = [];

    /**
     * Construct.
     *
     * @param DocumentFinder $finder For finding documents.
     * @param DocumentParser $parser For reading document annotations.
     */
    public function __construct($finder, $parser)
    {
        $this->parser = $parser;
        $this->finder = $finder;
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
        return $this->getDocumentReflectionMapping(new \ReflectionClass($this->finder->getNamespace($namespace)));
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
        $documents = $this->finder->getBundleDocumentPaths($bundle);
        $bundle = $this->finder->getBundle($bundle);

        // Loop through documents found in bundle.
        foreach ($documents as $document) {
            $documentReflection = new \ReflectionClass(
                substr($bundle, 0, strrpos($bundle, '\\')) .
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
     * Gathers annotation data from class.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array|null
     */
    private function getDocumentReflectionMapping(\ReflectionClass $reflectionClass)
    {
        $mapping = $this->parser->parse($reflectionClass);

        if ($mapping !== null) {
            $key = key($mapping);
            // TODO: generate proxy classes with setters and getters.
//            list($setters, $getters) = $this->getSettersAndGetters($reflectionClass, $mapping[$key]['properties']);
            $mapping[$key] = array_merge(
                $mapping[$key],
                [
                    'namespace' => $reflectionClass->getNamespaceName() . '\\' . $reflectionClass->getShortName(),
                    'class' => $reflectionClass->getShortName(),
                ]
            );
        }

        return $mapping;
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
            if (isset($this->aliases[$reflectionClass->getName()]) &&
                array_key_exists($property, $this->aliases[$reflectionClass->getName()])
            ) {
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
        $type = $this->parser->getPropertyAnnotationData($reflectionClass->getProperty($propertyName));

        $childReflection = new \ReflectionClass($this->finder->getNamespace($type->objectName));

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
            'namespace' => $this->finder->getNamespace($type->objectName),
            'multiple' => $type instanceof Property ? $type->multiple : false,
        ];
    }
}
