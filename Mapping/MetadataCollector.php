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
     * @var ProxyLoader
     */
    private $proxyLoader;

    /**
     * @var array Contains mappings gathered from bundle documents.
     */
    private $documents = [];

    /**
     * Constructor.
     *
     * @param DocumentFinder $finder      For finding documents.
     * @param DocumentParser $parser      For reading document annotations.
     * @param ProxyLoader    $proxyLoader For creating proxy documents.
     */
    public function __construct($finder, $parser, $proxyLoader)
    {
        $this->finder = $finder;
        $this->parser = $parser;
        $this->proxyLoader = $proxyLoader;
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

        $mappings = [];
        foreach ($this->getBundleMapping($bundle) as $type => $mapping) {
            $mappings[$type] = array_filter(
                array_merge(
                    ['properties' => $mapping['properties']],
                    $mapping['fields']
                )
            );
        }
        $this->documents[$bundle] = $mappings;

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
        $bundleNamespace = $this->finder->getBundle($bundle);
        $bundleNamespace = substr($bundleNamespace, 0, strrpos($bundleNamespace, '\\'));
        $documentDir = str_replace('/', '\\', $this->finder->getDocumentDir());

        // Loop through documents found in bundle.
        foreach ($this->finder->getBundleDocumentPaths($bundle) as $document) {
            $documentReflection = new \ReflectionClass(
                $bundleNamespace .
                '\\' . $documentDir .
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
     * @param \ReflectionClass $reflectionClass Document reflection class to read mapping from.
     *
     * @return array|null
     */
    private function getDocumentReflectionMapping(\ReflectionClass $reflectionClass)
    {
        $mapping = $this->parser->parse($reflectionClass);

        if ($mapping !== null) {
            $key = key($mapping);
            $mapping[$key] = array_merge(
                $mapping[$key],
                [
                    'proxyNamespace' => $this->proxyLoader->load($reflectionClass),
                    'namespace' => $reflectionClass->getNamespaceName() . '\\' . $reflectionClass->getShortName(),
                    'class' => $reflectionClass->getShortName(),
                ]
            );
        }

        return $mapping;
    }
}
