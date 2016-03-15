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

use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue;
use ONGR\ElasticsearchBundle\Service\Manager;

/**
 * Class DocumentIterator.
 */
class DocumentIterator extends AbstractResultsIterator
{
    /**
     * @var array
     */
    private $aggregations;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $rawData, Manager $manager, array $scroll = [])
    {
        if (isset($rawData['aggregations'])) {
            $this->aggregations = $rawData['aggregations'];
            unset($rawData['aggregations']);
        }

        parent::__construct($rawData, $manager, $scroll);
    }

    /**
     * Returns aggregations.
     *
     * @return array
     */
    public function getAggregations()
    {
        $aggregations = array();

        foreach ($this->aggregations as $key => $aggregation) {
            $aggregations[$key] = $this->getAggregation($key);
        }

        return $aggregations;
    }

    /**
     * Get a specific aggregation by name. It fetches from the top level only.
     *
     * @param string $name
     *
     * @return AggregationValue|null
     */
    public function getAggregation($name)
    {
        if (!isset($this->aggregations[$name])) {
            return null;
        }

        return new AggregationValue($this->aggregations[$name]);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $this->getConverter()->convertToDocument($document, $this->getManager());
    }
}
