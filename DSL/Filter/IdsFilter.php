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
 * Represents Elasticsearch "ids" filter.
 */
class IdsFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string[]
     */
    private $values;

    /**
     * @param string[] $values     Ids' values.
     * @param array    $parameters Optional parameters.
     */
    public function __construct($values, array $parameters = [])
    {
        $this->values = $values;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ids';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query['values'] = $this->values;

        $output = $this->processArray($query);

        return $output;
    }
}
