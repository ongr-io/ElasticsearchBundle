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
     * @param Manager        $manager Connection to act upon.
     * @param null|\DateTime $time    Date for which the suffix will be based on.
     *                                Current date if null.
     *
     * @return string
     */
    public function setNextFreeIndex(Manager $manager, \DateTime $time = null)
    {
        if ($time === null) {
            $time = new \DateTime();
        }

        $date = $time->format('Y.m.d');
        $indexName = $manager->getIndexName();

        $nameBase = $indexName . '-' . $date;
        $name = $nameBase;
        $i = 0;
        $manager->setIndexName($name);

        while ($manager->indexExists()) {
            $i++;
            $name = "{$nameBase}-{$i}";
            $manager->setIndexName($name);
        }

        return $name;
    }
}
