<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL\Filter;

use Ongr\ElasticsearchBundle\DSL\Bool\Bool;
use Ongr\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * AbstractFilter class.
 */
abstract class AbstractFilter
{
    /**
     * @var BuilderInterface
     */
    protected $filters;

    /**
     * Initializes bool filter.
     *
     * @param array $boolParams Bool parameters.
     */
    public function __construct($boolParams = [])
    {
        $this->filters = new Bool();
        $this->filters->setParameters($boolParams);
    }

    /**
     * @param BuilderInterface $filter   Filter.
     * @param string           $boolType Possible boolType values:
     *                                   - must
     *                                   - must_not
     *                                   - should.
     */
    public function addFilter(BuilderInterface $filter, $boolType = 'must')
    {
        $this->filters->addToBool($filter, $boolType);
    }

    /**
     * Overrides filters.
     *
     * @param BuilderInterface $filters
     *
     * @return $this
     */
    public function setFilter(BuilderInterface $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @param array $boolParams
     */
    public function setBoolParameters($boolParams)
    {
        $this->filters->setParameters($boolParams);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $output = [];
        $output[$this->filters->getType()] = $this->filters->toArray();

        return $output;
    }
}
