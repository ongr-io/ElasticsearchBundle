<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL\Query;

use Ongr\ElasticsearchBundle\DSL\BuilderInterface;
use Ongr\ElasticsearchBundle\DSL\Filter\AbstractFilter;

/**
 * Filtered query class.
 */
class FilteredQuery extends AbstractFilter implements BuilderInterface
{
    /**
     * @var Query Used inside filtered area.
     */
    private $query;

    /**
     * @param Query $query
     */
    public function __construct($query = null)
    {
        parent::__construct();
        $this->query = $query;
    }

    /**
     * @return Query $query
     */
    public function getQuery()
    {
        if ($this->query === null) {
            $this->query = new Query();
        }

        return $this->query;
    }

    /**
     * @param BuilderInterface $query
     */
    public function setQuery(BuilderInterface $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'filtered';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $output = [];
        $output['filter'] = parent::toArray();

        if ($this->query !== null) {
            $output['query'] = $this->query->toArray();
        }

        return $output;
    }
}
