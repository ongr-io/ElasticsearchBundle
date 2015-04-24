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

use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\DSL\Aggregation\AbstractAggregation;
use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator;
use ONGR\ElasticsearchBundle\Result\Suggestion\SuggestionIterator;

/**
 * This class is able to iterate over Elasticsearch result documents while casting data into models.
 */
class DocumentIterator extends AbstractResultsIterator
{
    /**
     * @var array
     */
    private $rawData;

    /**
     * @var array
     */
    private $bundlesMapping;

    /**
     * @var array
     */
    private $typesMapping;

    /**
     * @var AggregationIterator
     */
    private $aggregations;

    /**
     * @var SuggestionIterator
     */
    private $suggestions;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Constructor.
     *
     * @param array $rawData
     * @param array $typesMapping
     * @param array $bundlesMapping
     */
    public function __construct($rawData, $typesMapping, $bundlesMapping)
    {
        $this->rawData = $rawData;
        $this->typesMapping = $typesMapping;
        $this->bundlesMapping = $bundlesMapping;

        // Alias documents to have shorter path.
        if (isset($rawData['hits']['hits'])) {
            $this->documents = &$rawData['hits']['hits'];
        }
    }

    /**
     * Returns a converter.
     *
     * @return Converter
     */
    protected function getConverter()
    {
        if ($this->converter === null) {
            $this->converter = new Converter($this->typesMapping, $this->bundlesMapping);
        }

        return $this->converter;
    }

    /**
     * Converts raw array to document.
     *
     * @param array $rawData
     *
     * @return DocumentInterface
     *
     * @throws \LogicException
     */
    protected function convertDocument($rawData)
    {
        return $this->getConverter()->convertToDocument($rawData);
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    protected function getMapByType($type)
    {
        return $this->bundlesMapping[$this->typesMapping[$type]];
    }

    /**
     * Returns count of records found by given query.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->rawData['hits']['total'];
    }

    /**
     * Returns aggregations.
     *
     * @return AggregationIterator
     */
    public function getAggregations()
    {
        if (isset($this->rawData['aggregations'])) {
            $data = [];

            foreach ($this->rawData['aggregations'] as $key => $value) {
                $realKey = substr($key, strlen(AbstractAggregation::PREFIX));
                $data[$realKey] = $value;
            }

            unset($this->rawData['aggregations']);
            $this->aggregations = new AggregationIterator($data, $this->getConverter());
        } elseif ($this->aggregations === null) {
            $this->aggregations = new AggregationIterator([]);
        }

        return $this->aggregations;
    }

    /**
     * Returns suggestions.
     *
     * @return SuggestionIterator
     */
    public function getSuggestions()
    {
        if (isset($this->rawData['suggest'])) {
            $this->suggestions = new SuggestionIterator($this->rawData['suggest']);

            // Clear memory.
            unset($this->rawData['suggest']);
        }

        return $this->suggestions;
    }
}
