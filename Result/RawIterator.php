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

use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Aggregation\AbstractAggregation;

/**
 * Class DocumentIterator.
 */
class RawIterator extends AbstractResultsIterator
{
    /**
     * Returns aggregations.
     *
     * @return array
     */
    public function getAggregations()
    {
        return parent::getAggregations();
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $document;
    }

    /**
     * {@inheritdoc}
     */
    protected function getScrollResultsType()
    {
        return Repository::RESULTS_RAW_ITERATOR;
    }
}
