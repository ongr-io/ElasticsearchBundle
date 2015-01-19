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
 * Container for WarmerInterfaces.
 */
class WarmersContainer
{
    /**
     * @var WarmerInterface[]
     */
    private $warmers = [];

    /**
     * Warms up the cache.
     *
     * @return array
     */
    public function getWarmers()
    {
        $warmers = [];
        /** @var WarmerInterface $warmer */
        foreach ($this->warmers as $warmer) {
            $search = new Search();
            $warmer->warmUp($search);
            $warmers[$warmer->getName()] = $search->toArray();
        }

        return $warmers;
    }

    /**
     * @param array $warmers
     */
    public function setWarmers(array $warmers)
    {
        $this->warmers = [];
        foreach ($warmers as $warmer) {
            $this->addWarmer($warmer);
        }
    }

    /**
     * Adds cache warmer.
     *
     * @param WarmerInterface $warmer
     */
    public function addWarmer(WarmerInterface $warmer)
    {
        $this->warmers[] = $warmer;
    }
}
