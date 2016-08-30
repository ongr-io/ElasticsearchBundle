<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result;

use ONGR\ElasticsearchBundle\Service\Manager;

/**
 * This is the class for inner hit result with nested support.
 */
class InnerHitValue
{
    /**
     * @var array
     */
    private $rawData;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param array   $rawData
     * @param Manager $manager
     */
    public function __construct($rawData, Manager $manager)
    {
        $this->rawData = $rawData;
        $this->manager = $manager;
    }

    /**
     * Returns array of specific inner hit objects
     *
     * @param string $name
     *
     * @return object[]
     */
    public function getValue($name)
    {
        if (!isset($this->rawData[$name])) {
            return null;
        }

        $hits = [];

        foreach ($this->rawData[$name]['hits']['hits'] as $hit) {
            if (isset($hit['_parent'])) {
                $hits[] = $this->manager->getConverter()->convertToDocument($hit, $this->manager);
            } else {
                $fields = $hit;
                $metadata = $this->manager
                    ->getMetadataCollector()
                    ->getMappings(
                        $this->manager->getConfig()['mappings']
                    )[$hit['_type']];

                while (isset($fields['_nested'])) {
                    $fields = $fields['_nested'];
                    $metadata = $metadata['aliases'][$fields['field']];
                }

                $hits[] = $this->manager->getConverter()->assignArrayToObject(
                    $hit['_source'],
                    new $metadata['namespace'],
                    $metadata['aliases']
                );
            }
        };

        return $hits;
    }

    /**
     * Returns the count of inner hits for a specific hit
     *
     * @param string $name
     *
     * @return integer
     */
    public function getCount($name)
    {
        if (!isset($this->rawData[$name])) {
            return null;
        }

        return $this->rawData[$name]['hits']['total'];
    }

    /**
     * Returns inner hits for a specified inner hit
     *
     * @param string $name
     *
     * @return InnerHitValue[]|null
     */
    public function getInnerHits($name)
    {
        if (!isset($this->rawData[$name]['hits']['hits'][0]['inner_hits'])) {
            return null;
        }

        $hits = [];

        foreach ($this->rawData[$name]['hits']['hits'] as $hit) {
            $hits[] = new self($hit['inner_hits'], $this->manager);
        }

        return $hits;
    }
}
