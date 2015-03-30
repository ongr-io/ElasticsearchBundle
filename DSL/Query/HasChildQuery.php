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
use Ongr\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch has_child query class.
 */
class HasChildQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $type;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * @param string           $type
     * @param BuilderInterface $query
     * @param array            $parameters
     */
    public function __construct($type, BuilderInterface $query, array $parameters = [])
    {
        $this->type = $type;
        $this->query = $query;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'has_child';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'type' => $this->type,
            'query' => [
                $this->query->getType() => $this->query->toArray(),
            ],
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
