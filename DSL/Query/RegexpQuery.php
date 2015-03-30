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
 * Regexp query class.
 */
class RegexpQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string Field to be queried.
     */
    private $field;

    /**
     * @var string The actual regexp value to be used.
     */
    private $regexpValue;

    /**
     * @param string $field
     * @param string $regexpValue
     * @param array  $parameters
     */
    public function __construct($field, $regexpValue, array $parameters = [])
    {
        $this->field = $field;
        $this->regexpValue = $regexpValue;
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
            'value' => $this->regexpValue,
        ];

        $output = [
            $this->field => $this->processArray($query),
        ];

        return $output;
    }
}
