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
use ONGR\ElasticsearchBundle\Service\Repository;

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

        return new AggregationIterator($aggregations, $this->getConverter(), $this->getRepository());
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $this->getConverter()->convertToDocument($document, $this->getRepository());
    }

    /**
     * {@inheritdoc}
     */
    protected function getScrollResultsType()
    {
        return Repository::RESULTS_OBJECT;
    }
}
