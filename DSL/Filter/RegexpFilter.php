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
 * Represents Elasticsearch "regexp" filter.
 */
class RegexpFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $regexp;

    /**
     * @param string $field      Field name.
     * @param string $regexp     Regular expression.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($field, $regexp, array $parameters = [])
    {
        $this->field = $field;
        $this->regexp = $regexp;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'regexp';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'value' => $this->regexp,
        ];

        if ($this->hasParameter('flags')) {
            $query['flags'] = $this->getParameter('flags');
            unset($this->parameters['flags']);
        }

        $output = $this->processArray([$this->field => $query]);

        return $output;
    }
}
