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

class DocumentIterator extends AbstractResultsIterator
{
    public function getAggregations()
    {
        $aggregations = [];

        foreach (parent::getAggregations() as $key => $aggregation) {
            $aggregations[$key] = $this->getAggregation($key);
        }

        return $aggregations;
    }

    public function getAggregation($name)
    {
        $aggregations = parent::getAggregations();
        if (!array_key_exists($name, $aggregations)) {
            return null;
        }

        return new AggregationValue($aggregations[$name]);
    }

    protected function convertDocument(array $raw)
    {
        $data = $raw['_source'] ?? $raw['_fields'] ?? null;
        $data['_id'] = $raw['_id'] ?? null;

        return $this->getConverter()->convertArrayToDocument($this->getIndex()->getNamespace(), $data);
    }
}
