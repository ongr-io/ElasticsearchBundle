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

/**
 * Represents Elasticsearch "match_all" filter.
 *
 * A filter matches on all documents.
 */
class MatchAllFilter implements BuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'match_all';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return new \stdClass();
    }
}
