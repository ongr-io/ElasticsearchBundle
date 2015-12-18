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
use ONGR\ElasticsearchBundle\Document\DocumentInterface;

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

        // Checks if is mapped document or bundle.
        if (strpos($name, ':') !== false) {
            $bundleInfo = explode(':', $name);
            $bundle = $bundleInfo[0];
            $documentClass = $bundleInfo[1];

            $documents = $this->finder->getBundleDocumentPaths($bundle);
            $documents = array_filter(
                $documents,
                function ($document) use ($documentClass) {
                    if (pathinfo($document, PATHINFO_FILENAME) == $documentClass) {
                        return true;
                    }
                }
            );
        } else {
            $documents = $this->finder->getBundleDocumentPaths($name);
            $bundle = $name;
        }

        $mappings = [];
        $this->finder->getBundleClass($bundle);

        if (!count($documents)) {
            return [];
        }

        // Loop through documents found in bundle.
        foreach ($documents as $document) {
            $namespace = $this->getFileNamespace($document);
            if (!$namespace) {
                continue;
            }
            $documentReflection = new \ReflectionClass(
                $namespace . '\\' . pathinfo($document, PATHINFO_FILENAME)
            );

            $documentMapping = $this->getDocumentReflectionMapping($documentReflection);

            if (!array_key_exists('type', $documentMapping)) {
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
     * Resolves document elasticsearch type, use format: SomeBarBundle:AcmeDocument.
     *
     * @param string $document
     *
     * @return string
     */
    public function getDocumentType($document)
    {
        $mapping = $this->getMappingByNamespace($document);

        return $mapping['type'];
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
        if (isset($this->mappings[$namespace])) {
            return $this->mappings[$namespace];
        }

        $mapping = $this->getDocumentReflectionMapping(new \ReflectionClass($this->finder->getNamespace($namespace)));
        $this->cacheBundle($namespace, $mapping);

        return $mapping;
    }

    /**
     * Retrieves prepared mapping to sent to the elasticsearch client.
     *
     * @param array $bundles Manager config.
     *
     * @return array
     */
    public function getClientMapping(array $bundles)
    {
        /** @var array $typesMapping Array of filtered mappings for the elasticsearch client*/
        $typesMapping = [];

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
     */
    private function getDocumentReflectionMapping(\ReflectionClass $reflectionClass)
    {
        return $this->parser->parse($reflectionClass);
    }

    /**
     * Retrieves mapping from document.
     *
     * @param DocumentInterface $document
     *
     * @return array
     */
    public function getDocumentMapping(DocumentInterface $document)
    {
        return $this->getDocumentReflectionMapping(new \ReflectionObject($document));
    }

    /**
     * Returns mapping with metadata.
     *
     * @param string $namespace Bundle or document namespace.
     *
     * @return array
     */
    public function getMapping($namespace)
    {
        if (strpos($namespace, ':') === false) {
            return $this->getBundleMapping($namespace);
        }
        $mapping = $this->getMappingByNamespace($namespace);

        return $mapping === null ? [] : $mapping;
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
     * returns the namespace declared at the start of a file
     * @param $filepath
     * @return bool
     */
    private function getFileNamespace($filepath)
    {
        $exists = preg_match('/<\?php.+?namespace ([^;]+)/si', file_get_contents($filepath), $match);

        if ($exists && isset($match[1])) {
            return $match[1];
        }
        return false;
    }
}
