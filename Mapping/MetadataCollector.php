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
    private $cache;

    /**
     * @var array
     */
    private $types = [];

    /**
     * @param DocumentFinder $finder For finding documents.
     * @param DocumentParser $parser For reading document annotations.
     * @param CacheProvider  $cache  Cache provider to store the meta data for later use.
     */
    public function __construct($finder, $parser, $cache)
    {
        $this->finder = $finder;
        $this->parser = $parser;
        $this->cache = $cache;
    }

    /**
     * Fetches bundles mapping from documents.
     *
     * @param array $bundles Elasticsearch manager config. You can get bundles list from 'mappings' node.
     * @return array
     */
    public function getMappings(array $bundles)
    {
        $output = [];
        foreach ($bundles as $bundle) {
            $output = array_merge($output, $this->getBundleMapping($bundle));
        }

        return $output;
    }

    /**
     * Searches for documents in the bundle and tries to read them.
     *
     * @param string $bundle
     *
     * @return array Empty array on containing zero documents.
     */
    public function getBundleMapping($bundle)
    {
        $mappings = [];
        $bundleNamespace = $this->finder->getBundleClass($bundle);
        $bundleNamespace = substr($bundleNamespace, 0, strrpos($bundleNamespace, '\\'));

        // Checks if is mapped document or bundle.
        if (strpos($bundle, ':') !== false) {
            $documents = [];
        } else {
            $documents = $this->finder->getBundleDocumentPaths($bundle);
        }

        // Loop through documents found in bundle.
        foreach ($documents as $document) {
            $documentReflection = new \ReflectionClass(
                $bundleNamespace .
                '\\' . DocumentFinder::DOCUMENT_DIR .
                '\\' . pathinfo($document, PATHINFO_FILENAME)
            );

            $documentMapping = $this->getDocumentReflectionMapping($documentReflection);
            if ($documentMapping !== null) {
                $mappings = array_replace_recursive($mappings, $documentMapping);
            }
        }

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

        $type = array_shift(array_keys($mapping));

        return $type;
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
     * Retrieves mapping from local cache otherwise runs through bundle files.
     *
     * @param array $manager Manager config.
     *
     * @return array
     */
    public function getClientMapping($manager)
    {
        /** @var array $typesMapping Array of filtered mappings for the elasticsearch client*/
        $typesMapping = [];

        /** @var array $mappings All mapping info */
        $mappings = $this->getMappings($manager['mappings']);

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
     * @return array|null
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
     * @return array|null
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
}
