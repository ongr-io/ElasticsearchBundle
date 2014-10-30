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
 * ObjectIterator class.
 */
class ObjectIterator extends AbstractResultsIterator
{
    /**
     * Setters information.
     *
     * @var array
     */
    private $setters;

    /**
     * Raw data from Elasticsearch.
     *
     * @var array
     */
    private $rawData;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Constructor.
     *
     * @param Converter $converter
     * @param array     $rawData
     * @param array     $setters
     */
    public function __construct($converter, $rawData, $setters)
    {
        $this->converter = $converter;
        $this->rawData = $rawData;
        $this->setters = $setters;
        $this->converted = [];

        // Alias documents to have shorter path.
        $this->documents = &$rawData;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument($rawData)
    {
        return $this->converter
            ->assignArrayToObject($rawData, new $this->setters['namespace'](), $this->setters['properties']);
    }
}
