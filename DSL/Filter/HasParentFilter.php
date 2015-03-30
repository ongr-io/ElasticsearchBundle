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
use Ongr\ElasticsearchBundle\DSL\DslTypeAwareTrait;
use Ongr\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch has_parent filter.
 */
class HasParentFilter implements BuilderInterface
{
    use ParametersTrait;
    use DslTypeAwareTrait;

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
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($parentType, BuilderInterface $query, array $parameters = [])
    {
        $this->parentType = $parentType;
        $this->query = $query;
        $this->setParameters($parameters);
        $this->setDslType('filter');
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
            $this->getDslType() => [$this->query->getType() => $this->query->toArray()],
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
