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

use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator;
use ONGR\ElasticsearchBundle\Result\Aggregation\ValueAggregation;

/**
 * Class DocumentIterator.
 */
class DocumentIterator extends AbstractResultsIterator
{
    /**
     * Returns aggregations.
     *
     * @return AggregationIterator
     */
    public function getAggregations()
    {
        $aggregations = parent::getAggregations();

        return new AggregationIterator($aggregations);
    }

    /**
     * Get a specific aggregation by name. It fetches from the top level only.
     *
     * @param string $name
     *
     * @return null|AggregationIterator|ValueAggregation
     */
    public function getAggregation($name)
    {
        return $this->getAggregations()->find($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $this->getConverter()->convertToDocument($document, $this->getManager());
    }

    /**
     * {@inheritdoc}
     */
    protected function getScrollResultsType()
    {
        return Result::RESULTS_OBJECT;
    }
}
