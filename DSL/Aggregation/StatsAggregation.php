<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL\Aggregation;

use Ongr\ElasticsearchBundle\DSL\Aggregation\Type\MetricTrait;

/**
 * Class representing StatsAggregation.
 */
class StatsAggregation extends AbstractAggregation
{
    use MetricTrait;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'stats';
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        return $this->getField() ? ['field' => $this->getField()] : new \stdClass();
    }
}
