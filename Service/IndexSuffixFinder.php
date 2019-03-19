<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service;

class IndexSuffixFinder
{
    public function getNextFreeIndex(IndexService $index, \DateTime $time = null): string
    {
        if ($time === null) {
            $time = new \DateTime();
        }

        $date = $time->format('Y.m.d');
        $alias = $index->getIndexName();
        $indexName = $alias . '-' . $date;
        $i = 0;

        $client = $index->getClient();

        while ($client->indices()->exists(['index' => $indexName])) {
            $i++;
            $indexName = "{$indexName}-{$i}";
        }

        return $indexName;
    }
}
