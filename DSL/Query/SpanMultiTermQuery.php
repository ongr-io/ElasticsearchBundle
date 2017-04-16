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
 * Elasticsearch span multi term query.
 */
class SpanMultiTermQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * @param BuilderInterface $query
     * @param array            $parameters
     */
    public function __construct($query, array $parameters = [])
    {
        $this->query = $query;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_multi';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function toArray()
    {
        $query = [];
        $multiTermQueries = ['fuzzy', 'prefix', 'regexp', 'range', 'wildcard'];
        if (in_array($this->query->getType(), $multiTermQueries)) {
            $query['match'] = [$this->query->getType() => $this->query->toArray()];
        } else {
            throw new \InvalidArgumentException(
                'Invalid query. Valid MultiTermQueries are Fuzzy, Prefix, Regexp, Range, and Wildcard.'
            );
        }
        $output = $this->processArray($query);

        return $output;
    }
}
