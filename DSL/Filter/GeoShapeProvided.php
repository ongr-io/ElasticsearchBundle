<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DSL\Filter;

use ONGR\ElasticsearchBundle\DSL\BuilderInterface;
use ONGR\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch geo shape provided filter.
 */
class GeoShapeProvided implements BuilderInterface
{
    use ParametersTrait;

    const TYPE_ENVELOPE = 'envelope';
    const TYPE_MULTIPOINT = 'multipoint';
    const TYPE_POINT = 'point';
    const TYPE_MULTIPOLYGON = 'multipolygon';
    const TYPE_LINESTRING = 'linestring';
    const TYPE_POLYGON = 'polygon';
    const TYPE_CIRCLE = 'circle';

    /**
     * @var string
     */
    private $field;

    /**
     * @var array
     */
    private $coordinates;

    /**
     * @var string
     */
    private $shapeType;

    /**
     * @var string
     */
    private $radius;

    /**
     * @return string
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * @param string $radius
     */
    public function setRadius($radius)
    {
        $this->radius = $radius;
    }

    /**
     * @param string $field
     * @param array  $coordinates
     * @param string $shapeType
     * @param array  $parameters
     */
    public function __construct($field, array $coordinates, $shapeType = self::TYPE_ENVELOPE, array $parameters = [])
    {
        $this->field = $field;
        $this->coordinates = $coordinates;
        $this->shapeType = $shapeType;

        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'geo_shape';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];

        if ($this->shapeType !== self::TYPE_CIRCLE) {
            $query[$this->field]['shape'] = ['type' => $this->shapeType, 'coordinates' => $this->coordinates];
        } elseif ($this->shapeType === self::TYPE_CIRCLE && $this->getRadius()) {
            $query[$this->field]['shape'] = [
                'type' => $this->shapeType,
                'radius' => $this->getRadius(),
                'coordinates' => $this->coordinates,
            ];
        } else {
            throw new \LogicException('Shape type circle requires parameter "radius" to be set.');
        }

        $output = $this->processArray($query);

        return $output;
    }
}
