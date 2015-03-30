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

use Ongr\ElasticsearchBundle\DSL\Query\TermQuery;

/**
 * Elasticsearch span_term query class.
 */
class SpanTermQuery extends TermQuery implements SpanQueryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_term';
    }
}
