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
     * @var array Contains mappings gathered from bundle documents.
     */
    private $documents = [];

    /**
     * @param DocumentFinder $finder For finding documents.
     * @param DocumentParser $parser For reading document annotations.
     */
    public function __construct($finder, $parser)
    {
        $this->finder = $finder;
        $this->parser = $parser;
    }

    /**
     * Retrieves mapping from local cache otherwise runs through bundle files.
     *
     * @param string $namespace Bundle name to retrieve mappings from.
     * @param bool   $force     Forces to rescan bundles and skip local cache.
     *
     * @return array
     */
    public function getClientMapping($namespace, $force = false)
    {
        if (!$force && array_key_exists($namespace, $this->documents)) {
            return $this->documents[$namespace];
        }

        $mappings = [];
        foreach ($this->getMapping($namespace) as $type => $mapping) {
            if (!empty($mapping['properties'])) {
                $mappings[$type] = array_filter(
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

        $this->documents[$namespace] = $mappings;

        return $this->documents[$namespace];
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
     * Searches for documents in bundle and tries to read them.
     *
     * @param string $bundle
     *
     * @return array Empty array on containing zero documents.
     */
    public function getBundleMapping($bundle)
    {
        $mappings = [];
        $this->proxyPaths = [];
        $bundleNamespace = $this->finder->getBundleClass($bundle);
        $bundleNamespace = substr($bundleNamespace, 0, strrpos($bundleNamespace, '\\'));

        // Loop through documents found in bundle.
        foreach ($this->finder->getBundleDocumentPaths($bundle) as $document) {
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
}
