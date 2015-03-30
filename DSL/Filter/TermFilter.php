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
 * Represents Elasticsearch "term" filter.
 */
class TermFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $term;

    /**
     * @param string $field      Field name.
     * @param string $term       Field value.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($field, $term, array $parameters = [])
    {
        $this->field = $field;
        $this->term = $term;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'term';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [$this->field => $this->term];

        $output = $this->processArray($query);

        return $output;
    }
}
