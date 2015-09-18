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
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator;
use ONGR\ElasticsearchDSL\Aggregation\AbstractAggregation;

/**
 * Class DocumentIterator.
 */
class DocumentIterator extends AbstractResultsIterator implements \Countable, \Iterator
{
    use IteratorTrait;

    /**
     * Returns aggregations.
     *
     * @return AggregationIterator
     */
    public function getAggregations()
    {
        $aggregations = parent::getAggregations();

        foreach ($aggregations as $key => $value) {
            $realKey = substr($key, strlen(AbstractAggregation::PREFIX));
            $data[$realKey] = $value;
        }

        return new AggregationIterator($aggregations, $this->getConverter());
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        #TODO Check additionally if mappings are set, otherwise throw exception.
        return $this->getConverter()->convertToDocument($document, $this->getManagerConfig()['mappings']);
    }
}
