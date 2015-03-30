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
 * Represents Elasticsearch "Geo Bounding Box" filter.
 */
class GeoBoundingBoxFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var array
     */
    private $values;

    /**
     * @var string
     */
    private $field;

    /**
     * @param string $field
     * @param array  $values
     * @param array  $parameters
     */
    public function __construct($field, $values, array $parameters = [])
    {
        $this->field = $field;
        $this->values = $values;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'geo_bounding_box';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        if (count($this->values) === 2) {
            $query = [
                $this->field => [
                    'top_left' => $this->values[0],
                    'bottom_right' => $this->values[1],
                ],
            ];
        } elseif (count($this->values) === 4) {
            $query = [
                $this->field => [
                    'top' => $this->values[0],
                    'left' => $this->values[1],
                    'bottom' => $this->values[2],
                    'right' => $this->values[3],
                ],
            ];
        } else {
            throw new \LogicException('Geo Bounding Box filter must have 2 or 4 geo points set.');
        }

        $output = $this->processArray($query);

        return $output;
    }
}
