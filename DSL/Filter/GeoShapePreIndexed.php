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
 * Represents Elasticsearch "GeoShape" pre-indexed shape filter.
 */
class GeoShapePreIndexed implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $index;

    /**
     * @var string
     */
    private $path;

    /**
     * @param string $field      Field value.
     * @param string $id         The ID of the document that containing the pre-indexed shape.
     * @param string $type       Name of the index where the pre-indexed shape is.
     * @param string $index      Index type where the pre-indexed shape is.
     * @param string $path       The field specified as path containing the pre-indexed shape.
     * @param array  $parameters Additional parameters.
     */
    public function __construct($field, $id, $type, $index, $path, array $parameters = [])
    {
        $this->field = $field;
        $this->id = $id;
        $this->type = $type;
        $this->index = $index;
        $this->path = $path;

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

        $query[$this->field] = [
            'indexed_shape' => [
                'index' => $this->index,
                'type' => $this->type,
                'id' => $this->id,
                'path' => $this->path,
            ],
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
