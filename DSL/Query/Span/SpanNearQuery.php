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

/**
 * Elasticsearch span near query.
 */
class SpanNearQuery extends SpanOrQuery implements SpanQueryInterface
{
    /**
     * @var int
     */
    private $slop;

    /**
     * @return int
     */
    public function getSlop()
    {
        return $this->slop;
    }

    /**
     * @param int $slop
     */
    public function setSlop($slop)
    {
        $this->slop = $slop;
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
        foreach ($this->getQueries() as $type) {
            $query['clauses'][] = [$type->getType() => $type->toArray()];
        }
        $query['slop'] = $this->getSlop();
        $output = $this->processArray($query);

        return $output;
    }
}
