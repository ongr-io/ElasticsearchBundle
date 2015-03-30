<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\app\fixture\Warmer;

use Ongr\ElasticsearchBundle\Cache\WarmerInterface;
use Ongr\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use Ongr\ElasticsearchBundle\DSL\Search;

/**
 * Warmer for testing purposes.
 */
class FooWarmer implements WarmerInterface
{
    /**
     * {@inheritdoc}
     */
    public function warmUp(Search $search)
    {
        $search->addQuery(new MatchAllQuery());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'test_foo_warmer';
    }
}
