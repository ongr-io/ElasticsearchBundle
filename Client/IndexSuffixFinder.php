<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Client;

/**
 * Constructs index name with date's suffix.
 */
class IndexSuffixFinder
{
    /**
     * Constructs index name with date suffix. Sets name in the connection.
     *
     * E.g. 2022.03.22-5 (if 4 indexes exists already for given date)
     *
     * @param Connection     $connection Connection to act upon.
     * @param null|\DateTime $time       Date for which the suffix will be based on.
     *                                   Current date if null.
     *
     * @return string
     */
    public function setNextFreeIndex(Connection $connection, \DateTime $time = null)
    {
        if ($time === null) {
            $time = new \DateTime();
        }

        $suffix = null;
        $date = $time->format('Y.m.d');
        $indexName = $connection->getIndexName();

        $nameBase = $indexName . '-' . $date;
        $name = $nameBase;
        $i = 0;
        $connection->setIndexName($name);

        while ($connection->indexExists()) {
            $i++;
            $name = "{$nameBase}-{$i}";
            $connection->setIndexName($name);
        }

        return $name;
    }
}
