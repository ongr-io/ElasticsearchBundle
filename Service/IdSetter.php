<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service;

use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;

/**
 * Sets ids to persisted documents
 */
class IdSetter
{
    /**
     * Documents that are added to bulk
     * @var object[]
     */
    private $persistedDocuments;

    /**
     * Mappings of the persisted documents types
     * @var array
     */
    private $mappings;

    /**
     * @var MetadataCollector
     */
    private $collector;

    public function __construct(MetadataCollector $collector)
    {
        $this->collector = $collector;
    }

    /**
     * @param object $document
     */
    public function persist($document)
    {
        $class = get_class($document);

        if (!isset($this->mappings[$class])) {
            $this->mappings[$class] = $this->collector->getMapping($class)['aliases']['_id'];
        }

        $idMapping = $this->mappings[$class];

        if ($idMapping['propertyType'] == 'public') {
            $property = $idMapping['propertyName'];
            $id = $document->$property;
        } else {
            $method = $idMapping['methods']['getter'];
            $id = $document->$method();
        }

        if (!$id) {
            $this->persistedDocuments[] = $document;
        }
    }

    /**
     * @param array $response
     */
    public function addIds(array $response)
    {
        if (empty($this->persistedDocuments) || $response['errors']) {
            return;
        }

        foreach ($response['items'] as $object) {
            if (isset($object['create'])) {
                $document = array_shift($this->persistedDocuments);
                $this->setId($document, $object['create']['_id']);
            }
        }
    }

    /**
     * @param object $document
     * @param string $id
     */
    private function setId($document, $id)
    {
        $class = get_class($document);

        $idMapping = $this->mappings[$class];

        if ($idMapping['propertyType'] == 'public') {
            $property = $idMapping['propertyName'];
            $document->$property = $id;
        } else {
            $method = $idMapping['methods']['setter'];
            $document->$method($id);
        }
    }
}