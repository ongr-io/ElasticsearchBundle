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
 * Elasticsearch Span not query.
 */
class SpanNotQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface
     */
    private $include;

    /**
     * @var BuilderInterface
     */
    private $exclude;

    /**
     * @param BuilderInterface $include
     * @param BuilderInterface $exclude
     * @param array            $parameters
     */
    public function __construct(BuilderInterface $include, BuilderInterface $exclude, array $parameters = [])
    {
        $this->include = $include;
        $this->exclude = $exclude;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_not';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'include' => [$this->include->getType() => $this->include->toArray()],
            'exclude' => [$this->exclude->getType() => $this->exclude->toArray()],
        ];

        return $query;
    }
}
