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
 * Elasticsearch ids query class.
 */
class IdsQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var array
     */
    private $values;

    /**
     * @param array $values
     * @param array $parameters
     */
    public function __construct(array $values, array $parameters = [])
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
        $query = [
            'values' => $this->values,
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
