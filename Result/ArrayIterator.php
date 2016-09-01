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
class ArrayIterator extends AbstractResultsIterator
{
    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        if (array_key_exists('_source', $document)) {
            return $document['_source'];
        } elseif (array_key_exists('fields', $document)) {
            return array_map('reset', $document['fields']);
        }

        return $document;
    }
}
