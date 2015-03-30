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

use Ongr\ElasticsearchBundle\DSL\Aggregation\Type\BucketingTrait;

/**
 * Class representing GlobalAggregation.
 */
class GlobalAggregation extends AbstractAggregation
{
    use BucketingTrait;

    /**
     * {@inheritdoc}
     */
    public function setField($field)
    {
        throw new \LogicException("Global aggregation, doesn't support `field` parameter");
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'global';
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        return new \stdClass();
    }
}
