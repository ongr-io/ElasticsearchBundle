<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DSL\Query;

use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch span or query.
 */
class SpanOrQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface[]
     */
    private $queries = [];

    /**
     * @param array $queries
     * @param array $parameters
     */
    public function __construct(array $queries = [], array $parameters = [])
    {
        foreach ($queries as $query) {
            $this->queries[] = $query;
        }
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_or';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        foreach ($this->queries as $type) {
            $data = [$type->getType() => $type->toArray()];
            $query['clauses'][] = $data;
        }
        $output = $this->processArray($query);

        return $output;
    }
}
