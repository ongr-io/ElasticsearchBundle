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
use Ongr\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Class representing filters aggregation.
 */
class FiltersAggregation extends AbstractAggregation
{
    use BucketingTrait;

    /**
     * @var BuilderInterface[]
     */
    private $filters = [];

    /**
     * @var bool
     */
    private $anonymous = false;

    /**
     * @param bool $anonymous
     *
     * @return FiltersAggregation
     */
    public function setAnonymous($anonymous)
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    /**
     * @param BuilderInterface $filter
     * @param string           $name
     *
     * @throws \LogicException
     *
     * @return FiltersAggregation
     */
    public function addFilter(BuilderInterface $filter, $name = '')
    {
        if ($this->anonymous === false && empty($name)) {
            throw new \LogicException('In not anonymous filters filter name must be set.');
        } elseif ($this->anonymous === false && !empty($name)) {
            $this->filters['filters'][$name] = [$filter->getType() => $filter->toArray()];
        } else {
            $this->filters['filters'][] = [$filter->getType() => $filter->toArray()];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        return $this->filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'filters';
    }
}
