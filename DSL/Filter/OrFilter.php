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

use Ongr\ElasticsearchBundle\DSL\BuilderInterface;
use Ongr\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "or" filter.
 */
class OrFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface[]
     */
    private $filters;

    /**
     * @param BuilderInterface[] $filters    Filters.
     * @param array              $parameters Optional parameters.
     */
    public function __construct($filters, array $parameters = [])
    {
        $this->filters = $filters;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'or';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];

        foreach ($this->filters as $filter) {
            $query['filters'][] = [$filter->getType() => $filter->toArray()];
        }

        $output = $this->processArray($query);

        return $output;
    }
}
