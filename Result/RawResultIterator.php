<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Result;

/**
 * This class is able to iterate over raw result.
 */
class RawResultIterator extends AbstractResultsIterator
{
    /**
     * @var array
     */
    protected $rawData;

    /**
     * Constructor.
     *
     * @param array $rawData
     */
    public function __construct($rawData)
    {
        $this->rawData = $rawData;

        // Alias documents to have shorter path.
        if (isset($rawData['hits']['hits'])) {
            $this->documents = &$rawData['hits']['hits'];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument($rawData)
    {
        return $rawData;
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
}
