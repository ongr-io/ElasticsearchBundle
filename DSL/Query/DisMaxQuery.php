<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL\Query;

use Ongr\ElasticsearchBundle\DSL\BuilderInterface;
use Ongr\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch dis max query class.
 */
class DisMaxQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface[]
     */
    private $queries = [];

    /**
     * Initializes Dis Max query.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->setParameters($parameters);
    }

    /**
     * Add query.
     *
     * @param BuilderInterface $query
     *
     * @return DisMaxQuery
     */
    public function addQuery(BuilderInterface $query)
    {
        $this->queries[] = $query;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'dis_max';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        foreach ($this->queries as $type) {
            $query['queries'][] = [$type->getType() => $type->toArray()];
        }
        $output = $this->processArray($query);

        return $output;
    }
}
