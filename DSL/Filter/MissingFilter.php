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
 * Represents Elasticsearch "missing" filter.
 */
class MissingFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @param string $field      Field name.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($field, array $parameters = [])
    {
        $this->field = $field;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'missing';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query['field'] = $this->field;

        $output = $this->processArray($query);

        return $output;
    }
}
