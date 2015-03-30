<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL\Query\Span;

use Ongr\ElasticsearchBundle\DSL\BuilderInterface;
use Ongr\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch span multi term query.
 */
class SpanMultiTermQuery implements SpanQueryInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * Accepts one of fuzzy, prefix, term range, wildcard, regexp query.
     *
     * @param BuilderInterface $query
     * @param array            $parameters
     */
    public function __construct(BuilderInterface $query, array $parameters = [])
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
        $query['match'] = [$this->query->getType() => $this->query->toArray()];
        $output = $this->processArray($query);

        return $output;
    }
}
