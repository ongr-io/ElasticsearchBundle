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
use ONGR\ElasticsearchBundle\Exception\DocumentParserException;
use ONGR\ElasticsearchBundle\Exception\MissingDocumentAnnotationException;

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
     * @param DocumentFinder $finder For finding documents.
     * @param DocumentParser $parser For reading document annotations.
     * @param CacheProvider  $cache  Cache provider to store the meta data for later use.
     */
    public function __construct($finder, $parser, $cache = null)
    {
        $this->finder = $finder;
        $this->parser = $parser;
        $this->cache = $cache;
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
        foreach ($bundles as $name => $bundleConfig) {
            // Backward compatibility hack for support.
            if (!is_array($bundleConfig)) {
                $name = $bundleConfig;
                $bundleConfig = [];
            }
            $mappings = $this->getBundleMapping($name, $bundleConfig);

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
     * @param array $config Bundle configuration
     *
     * @return array Empty array on containing zero documents.
     */
    public function getBundleMapping($name, $config = [])
    {
        if (!is_string($name)) {
            throw new \LogicException('getBundleMapping() in the Metadata collector expects a string argument only!');
        }

        $cacheName =  'ongr.metadata.mapping.' . md5($name.serialize($config));

        $this->enableCache && $mappings = $this->cache->fetch($cacheName);

        if (isset($mappings) && false !== $mappings) {
            return $mappings;
        }

        $mappings = [];
        $documentDir = isset($config['document_dir']) ? $config['document_dir'] : $this->finder->getDocumentDir();

        // Handle the case when single document mapping requested
        // Usage od ":" in name is deprecated. This if is only for BC.
        if (strpos($name, ':') !== false) {
            list($bundle, $documentClass) = explode(':', $name);
            $documents = $this->finder->getBundleDocumentClasses($bundle);
            $documents = in_array($documentClass, $documents) ? [$documentClass] : [];
        } else {
            $documents = $this->finder->getBundleDocumentClasses($name, $documentDir);
            $bundle = $name;
        }

        $bundleNamespace = $this->finder->getBundleClass($bundle);
        $bundleNamespace = substr($bundleNamespace, 0, strrpos($bundleNamespace, '\\'));

        if (!count($documents)) {
            return [];
        }

        // Loop through documents found in bundle.
        foreach ($documents as $document) {
            $documentReflection = new \ReflectionClass(
                $bundleNamespace .
                '\\' . str_replace('/', '\\', $documentDir) .
                '\\' . $document
            );

            try {
                $documentMapping = $this->getDocumentReflectionMapping($documentReflection);
                if (!$documentMapping) {
                    continue;
                }
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

        $this->enableCache && $this->cache->save($cacheName, $mappings);

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
     * Prepares analysis node for Elasticsearch client.
     *
     * @param array $bundles
     * @param array $analysisConfig
     *
     * @return array
     */
    public function getClientAnalysis(array $bundles, $analysisConfig = [])
    {
        $cacheName = 'ongr.metadata.analysis.'.md5(serialize($bundles));
        $this->enableCache && $typesAnalysis = $this->cache->fetch($cacheName);

        if (isset($typesAnalysis) && false !== $typesAnalysis) {
            return $typesAnalysis;
        }

        $typesAnalysis = [
            'analyzer' => [],
            'filter' => [],
            'tokenizer' => [],
            'char_filter' => [],
            'normalizer' => [],
        ];

        /** @var array $mappings All mapping info */
        $mappings = $this->getMappings($bundles);

        foreach ($mappings as $type => $metadata) {
            foreach ($metadata['analyzers'] as $analyzerName) {
                if (isset($analysisConfig['analyzer'][$analyzerName])) {
                    $analyzer = $analysisConfig['analyzer'][$analyzerName];
                    $typesAnalysis['analyzer'][$analyzerName] = $analyzer;
                    $typesAnalysis['filter'] = $this->getAnalysisNodeConfiguration(
                        'filter',
                        $analyzer,
                        $analysisConfig,
                        $typesAnalysis['filter']
                    );
                    $typesAnalysis['tokenizer'] = $this->getAnalysisNodeConfiguration(
                        'tokenizer',
                        $analyzer,
                        $analysisConfig,
                        $typesAnalysis['tokenizer']
                    );
                    $typesAnalysis['char_filter'] = $this->getAnalysisNodeConfiguration(
                        'char_filter',
                        $analyzer,
                        $analysisConfig,
                        $typesAnalysis['char_filter']
                    );
                }
            }
        }

        if (isset($analysisConfig['normalizer'])) {
            $typesAnalysis['normalizer'] = $analysisConfig['normalizer'];
        }

        $this->enableCache && $this->cache->save($cacheName, $typesAnalysis);

        return $typesAnalysis;
    }

    /**
     * Prepares analysis node content for Elasticsearch client.
     *
     * @param string $type Node type: filter, tokenizer or char_filter
     * @param array $analyzer Analyzer from which used helpers will be extracted.
     * @param array $analysisConfig Pre configured analyzers container
     * @param array $container Current analysis container where prepared helpers will be appended.
     *
     * @return array
     */
    private function getAnalysisNodeConfiguration($type, $analyzer, $analysisConfig, $container = [])
    {
        if (isset($analyzer[$type])) {
            if (is_array($analyzer[$type])) {
                foreach ($analyzer[$type] as $filter) {
                    if (isset($analysisConfig[$type][$filter])) {
                        $container[$filter] = $analysisConfig[$type][$filter];
                    }
                }
            } else {
                if (isset($analysisConfig[$type][$analyzer[$type]])) {
                    $container[$analyzer[$type]] = $analysisConfig[$type][$analyzer[$type]];
                }
            }
        }
        return $container;
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
        $cacheName = 'ongr.metadata.document.'.md5($namespace);

        $namespace = $this->getClassName($namespace);
        $this->enableCache && $mapping = $this->cache->fetch($cacheName);

        if (isset($mapping) && false !== $mapping) {
            return $mapping;
        }

        $mapping = $this->getDocumentReflectionMapping(new \ReflectionClass($namespace));

        $this->enableCache && $this->cache->save($cacheName, $mapping);

        return $mapping;
    }

    /**
     * Returns fully qualified class name.
     *
     * @param string $className
     * @param string $directory The name of the directory
     *
     * @return string
     */
    public function getClassName($className, $directory = null)
    {
        return $this->finder->getNamespace($className, $directory);
    }
}
