<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DSL\Query;

use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch match query class.
 */
class MatchQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $query;

    /**
     * @param string $field
     * @param string $query
     * @param array  $parameters
     */
    public function __construct($field, $query, array $parameters = [])
    {
        $this->field = $field;
        $this->query = $query;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'match';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'query' => $this->query,
        ];

        $output = [
            $this->field => $this->processArray($query),
        ];

        return $output;
    }
}
