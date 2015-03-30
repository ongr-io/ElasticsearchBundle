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
 * Elasticsearch terms query class.
 */
class TermsQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var array
     */
    private $tags;

    /**
     * @param string $field
     * @param array  $tags
     * @param array  $parameters
     */
    public function __construct($field, $tags, array $parameters = [])
    {
        $this->field = $field;
        $this->tags = $tags;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'terms';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            $this->field => $this->tags,
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
