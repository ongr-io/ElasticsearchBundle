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
 * Elasticsearch Fuzzy Like This query class.
 */
class FuzzyLikeThisQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $likeText;

    /**
     * @param array  $fields
     * @param string $likeText
     * @param array  $parameters
     */
    public function __construct($fields, $likeText, array $parameters = [])
    {
        $this->fields = $fields;
        $this->likeText = $likeText;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'fuzzy_like_this';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        if ($this->fields) {
            $query['fields'] = $this->fields;
            $query['like_text'] = $this->likeText;
        } else {
            $query['like_text'] = $this->likeText;
        }
        $output = $this->processArray($query);

        return $output;
    }
}
