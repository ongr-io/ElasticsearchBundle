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
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * Elasticsearch span near query.
 */
class SpanNearQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface[]
     */
    private $queries = [];

    /**
     * @param BuilderInterface[] $queries
     * @param array              $parameters
     *
     * @throws \LogicException
     */
    public function __construct(array $queries = [], array $parameters = [])
    {
        $this->queries = $queries;
        $this->setParameters($parameters);
        if (!$this->hasParameter('slop')) {
            throw new \LogicException('Span near query must have a slop parameter set for it.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_near';
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
