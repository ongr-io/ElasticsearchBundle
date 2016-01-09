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

use Doctrine\Common\Cache\CacheProvider;
use ONGR\ElasticsearchBundle\Mapping\Exception\DocumentParserException;
use ONGR\ElasticsearchBundle\Mapping\Exception\MissingDocumentAnnotationException;

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
     * @var CacheProvider
     */
    private $cache = null;

    /**
     * @var bool
     */
    private $enableCache = false;

    /**
     * Bundles mappings local cache container. Could be stored as the whole bundle or as single document.
     * e.g. AcmeDemoBundle, AcmeDemoBundle:Product.
     *
     * @var mixed
     */
    private $mappings = [];

    /**
     * @param DocumentFinder $finder For finding documents.
     * @param DocumentParser $parser For reading document annotations.
     * @param CacheProvider  $cache  Cache provider to store the meta data for later use.
     */
    public function __construct($finder, $parser, $cache = null)
    {
        $this->finder = $finder;
        $this->parser = $parser;
        $this->cache = $cache;

        if ($this->cache) {
            $this->mappings = $this->cache->fetch('ongr.metadata.mappings');
        }
    }

    /**
     * Enables metadata caching.
     *
     * @param bool $enableCache
     */
    public function setEnableCache($enableCache)
    {
        $this->enableCache = $enableCache;
    }

    /**
     * Fetches bundles mapping from documents.
     *
     * @param string[] $bundles Elasticsearch manager config. You can get bundles list from 'mappings' node.
     * @return array
     */
    public function getMappings(array $bundles)
    {
        $output = [];
        foreach ($bundles as $bundle) {
            $mappings = $this->getBundleMapping($bundle);

            $alreadyDefinedTypes = array_intersect_key($mappings, $output);
            if (count($alreadyDefinedTypes)) {
                throw new \LogicException(
                    implode(',', array_keys($alreadyDefinedTypes)) .
                    ' type(s) already defined in other document, you can use the same ' .
                    'type only once in a manager definition.'
                );
            }

            $output = array_merge($output, $mappings);
        }

        return $output;
    }

    /**
     * Searches for documents in the bundle and tries to read them.
     *
     * @param string $name
     *
     * @return array Empty array on containing zero documents.
     */
    public function getBundleMapping($name)
    {
        if (!is_string($name)) {
            throw new \LogicException('getBundleMapping() in the Metadata collector expects a string argument only!');
        }

        if (isset($this->mappings[$name])) {
            return $this->mappings[$name];
        }

        // Handle the case when single document mapping requested
        if (strpos($name, ':') !== false) {
            list($bundle, $documentClass) = explode(':', $name);
            $documents = $this->finder->getBundleDocumentClasses($bundle);
            $documents = in_array($documentClass, $documents) ? [$documentClass] : [];
        } else {
            $documents = $this->finder->getBundleDocumentClasses($name);
            $bundle = $name;
        }

        $mappings = [];
        $bundleNamespace = $this->finder->getBundleClass($bundle);
        $bundleNamespace = substr($bundleNamespace, 0, strrpos($bundleNamespace, '\\'));

        if (!count($documents)) {
            return [];
        }

        // Loop through documents found in bundle.
        foreach ($documents as $document) {
            $documentReflection = new \ReflectionClass(
                $bundleNamespace .
                '\\' . DocumentFinder::DOCUMENT_DIR .
                '\\' . $document
            );

            try {
                $documentMapping = $this->getDocumentReflectionMapping($documentReflection);
            } catch (MissingDocumentAnnotationException $exception) {
                // Not a document, just ignore
                continue;
            }

            if (!array_key_exists($documentMapping['type'], $mappings)) {
                $documentMapping['bundle'] = $bundle;
                $mappings = array_merge($mappings, [$documentMapping['type'] => $documentMapping]);
            } else {
                throw new \LogicException(
                    $bundle . ' has 2 same type names defined in the documents. ' .
                    'Type names must be unique!'
                );
            }
        }

        $this->cacheBundle($name, $mappings);

        return $mappings;
    }

    /**
     * @param array $manager
     *
     * @return array
     */
    public function getManagerTypes($manager)
    {
        $mapping = $this->getMappings($manager['mappings']);

        return array_keys($mapping);
    }

    /**
     * Resolves Elasticsearch type by document class.
     *
     * @param string $className FQCN or string in AppBundle:Document format
     *
     * @return string
     */
    public function getDocumentType($className)
    {
        $mapping = $this->getMapping($className);

        return $mapping['type'];
    }

    /**
     * Retrieves prepared mapping to sent to the elasticsearch client.
     *
     * @param array $bundles Manager config.
     *
     * @return array|null
     */
    public function getClientMapping(array $bundles)
    {
        /** @var array $typesMapping Array of filtered mappings for the elasticsearch client*/
        $typesMapping = null;

        /** @var array $mappings All mapping info */
        $mappings = $this->getMappings($bundles);

        foreach ($mappings as $type => $mapping) {
            if (!empty($mapping['properties'])) {
                $typesMapping[$type] = array_filter(
                    array_merge(
                        ['properties' => $mapping['properties']],
                        $mapping['fields']
                    ),
                    function ($value) {
                        return (bool)$value || is_bool($value);
                    }
                );
            }
        }

        return $typesMapping;
    }

    /**
     * Gathers annotation data from class.
     *
     * @param \ReflectionClass $reflectionClass Document reflection class to read mapping from.
     *
     * @return array
     * @throws DocumentParserException
     */
    private function getDocumentReflectionMapping(\ReflectionClass $reflectionClass)
    {
        return $this->parser->parse($reflectionClass);
    }

    /**
     * Returns single document mapping metadata.
     *
     * @param string $namespace Document namespace
     *
     * @return array
     * @throws DocumentParserException
     */
    public function getMapping($namespace)
    {
        $namespace = $this->getClassName($namespace);

        if (isset($this->mappings[$namespace])) {
            return $this->mappings[$namespace];
        }

        $mapping = $this->getDocumentReflectionMapping(new \ReflectionClass($namespace));
        $this->cacheBundle($namespace, $mapping);

        return $mapping;
    }

    /**
     * Adds metadata information to the cache storage.
     *
     * @param string $bundle
     * @param array  $mapping
     */
    private function cacheBundle($bundle, array $mapping)
    {
        if ($this->enableCache) {
            $this->mappings[$bundle] = $mapping;
            $this->cache->save('ongr.metadata.mappings', $this->mappings);
        }
    }

    /**
     * Returns fully qualified class name.
     *
     * @param string $className
     *
     * @return string
     */
    public function getClassName($className)
    {
        return $this->finder->getNamespace($className);
    }
}
