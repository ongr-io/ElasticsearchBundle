<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Cache;

use ONGR\ElasticsearchBundle\DSL\Search;

/**
 *  Interface for warming search cache.
 */
interface WarmerInterface
{
    /**
     * Warms up search using warmers api.
     *
     * @param Search $search
     *
     * @link http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-warmers.html
     */
    public function warmUp(Search $search);

    /**
     * Returns warmer name.
     *
     * @return string
     */
    public function getName();
}
