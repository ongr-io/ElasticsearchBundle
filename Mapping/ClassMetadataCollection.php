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

/**
 * Holds gathered metadata for manager.
 */
class ClassMetadataCollection
{
    /**
     * @var ClassMetadata[]
     */
    private $metadata;

    /**
     * @var array
     */
    private $typesMap = [];

    /**
     * @param ClassMetadata[] $metadata
     */
    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Returns type map.
     *
     * @return array
     */
    public function getTypesMap()
    {
        if (empty($this->typesMap)) {
            $this->typesMap = $this->extractTypeMap();
        }

        return $this->typesMap;
    }

    /**
     * Returns metadata.
     *
     * @param array $repositories
     *
     * @return ClassMetadata[]
     */
    public function getMetadata($repositories = [])
    {
        if (!empty($repositories)) {
            return array_intersect_key($this->metadata, array_flip($repositories));
        }

        return $this->metadata;
    }

    /**
     * Extracts type map from metadata.
     *
     * @return array
     */
    private function extractTypeMap()
    {
        $out = [];

        foreach ($this->metadata as $repository => $data) {
            $out[$data->getType()] = $repository;
        }

        return $out;
    }
}
