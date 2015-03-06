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
 * Elasticsearch span first query.
 */
class SpanFirstQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * @param BuilderInterface $query
     * @param array            $parameters
     *
     * @throws \LogicException
     */
    public function __construct($query, array $parameters = [])
    {
        $this->query = $query;
        $this->setParameters($parameters);
        if (!$this->hasParameter('end')) {
            throw new \LogicException('Span first query must have an end parameter set for it.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_first';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        $query['match'] = [$this->query->getType() => $this->query->toArray()];
        $output = $this->processArray($query);

        return $output;
    }
}
