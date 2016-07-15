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
use ONGR\ElasticsearchBundle\Service\Manager;

/**
 * This is the class for plain aggregation result with nested aggregations support.
 */
class InnerHitValue
{
    /**
     * @var array
     */
    private $rawData;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * Constructor.
     *
     * @param array   $rawData
     * @param Manager $manager
     */
    public function __construct($rawData, Manager $manager)
    {
        $this->rawData = $rawData;
        $this->manager = $manager;
    }
}
