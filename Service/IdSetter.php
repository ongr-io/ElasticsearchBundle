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
            $mapping = $this->collector->getMapping($class);
            if (isset($mapping['aliases']['_id'])) {
                $this->mappings[$class] = $mapping['aliases']['_id'];
            } else {
                return;
            }
        }

        $idMapping = $this->mappings[$class];

        if ($idMapping['propertyType'] == 'public') {
            $id = $document->{$idMapping['propertyName']};
        } else {
            $id = $document->{$idMapping['methods']['getter']}();
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
                $document = current($this->persistedDocuments);

                if (!$document ||
                    !isset($this->mappings[ $class = get_class($document)])) {
                    continue;
                }

                if ($this->mappings[$class]['propertyType'] == 'public') {
                    $document->{$this->mappings[$class]['propertyName']} = $object['create']['_id'];
                } else {
                    $document->{$this->mappings[$class]['methods']['setter']}($object['create']['_id']);
                }

                next($this->persistedDocuments);
            }
        }
        $this->persistedDocuments = [];
    }
}
