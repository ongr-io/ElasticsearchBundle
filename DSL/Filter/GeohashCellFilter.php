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
use Ongr\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "Geohash Cell" filter.
 */
class GeohashCellFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var mixed
     */
    private $location;

    /**
     * @param string $field
     * @param mixed  $location
     * @param array  $parameters
     */
    public function __construct($field, $location, array $parameters = [])
    {
        $this->field = $field;
        $this->location = $location;

        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'geohash_cell';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        $query[$this->field] = $this->location;
        $output = $this->processArray($query);

        return $output;
    }
}
