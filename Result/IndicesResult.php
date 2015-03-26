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

/**
 * This class is able to iterate over raw result.
 */
class IndicesResult
{
    /**
     * @var array
     */
    private $rawData;

    /**
     * @param array $rawData
     */
    public function __construct(array $rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * @return array
     */
    public function getTotal()
    {
        return $this->extract(func_get_args(), 'total');
    }

    /**
     * @return array
     */
    public function getFailed()
    {
        return $this->extract(func_get_args(), 'failed');
    }

    /**
     * @return array
     */
    public function getSuccessful()
    {
        return $this->extract(func_get_args(), 'successful');
    }

    /**
     * Returns full response.
     *
     * @return array
     */
    public function getRaw()
    {
        return $this->rawData;
    }

    /**
     * Extracts data from response.
     *
     * @param array  $indices
     * @param string $name
     *
     * @return array
     */
    private function extract($indices, $name)
    {
        if (!empty($indices)) {
            return $this->getSelectedIndices($indices, $name);
        } else {
            return $this->getAllIndices($name);
        }
    }

    /**
     * Extracts response from given indices.
     *
     * @param array  $indices
     * @param string $name
     *
     * @return array
     */
    private function getSelectedIndices($indices, $name)
    {
        $results = [];
        $existIndexes = array_intersect_key($this->rawData['_indices'], array_flip($indices));
        foreach ($existIndexes as $index => $value) {
            $results[$index] = $this->getRaw()['_indices'][$index]['_shards'][$name];
        }

        return $results;
    }

    /**
     * Extract response from all indices.
     *
     * @param string $name
     *
     * @return array
     */
    private function getAllIndices($name)
    {
        $results = [];
        foreach ($this->getRaw()['_indices'] as $index => $value) {
            $results[$index] = $value['_shards'][$name];
        }

        return $results;
    }
}
