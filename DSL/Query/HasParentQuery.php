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
 * Elasticsearch has_parent query class.
 */
class HasParentQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $parentType;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * @param string           $parentType
     * @param BuilderInterface $query
     * @param array            $parameters
     */
    public function __construct($parentType, BuilderInterface $query, array $parameters = [])
    {
        $this->parentType = $parentType;
        $this->query = $query;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'has_parent';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'parent_type' => $this->parentType,
            'query' => [
                $this->query->getType() => $this->query->toArray(),
            ],
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
