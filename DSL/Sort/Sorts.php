<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL\Sort;

use Ongr\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Container for sorts.
 */
class Sorts implements BuilderInterface
{
    /**
     * @var AbstractSort[] Sorts collection.
     */
    private $sorts = [];

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'sort';
    }

    /**
     * @param AbstractSort $sort
     */
    public function addSort(AbstractSort $sort)
    {
        $this->sorts[$sort->getType()] = $sort;
    }

    /**
     * Check if we have any sorting set.
     *
     * @return bool
     */
    public function isRelevant()
    {
        return !empty($this->sorts);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $value = [];
        foreach ($this->sorts as $sort) {
            $value[$sort->getType()] = $sort->toArray();
        }

        return $value;
    }
}
