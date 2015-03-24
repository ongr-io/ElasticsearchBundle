<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DSL\Aggregation;

use ONGR\ElasticsearchBundle\DSL\Aggregation\Type\BucketingTrait;

/**
 * Class representing Histogram aggregation.
 */
class HistogramAggregation extends AbstractAggregation
{
    use BucketingTrait;

    const DIRECTION_ASC = 'asc';
    const DIRECTION_DESC = 'desc';

    /**
     * @var int
     */
    private $interval;

    /**
     * @var int
     */
    private $minDocCount;

    /**
     * @var array
     */
    private $extendedBounds;

    /**
     * @var string
     */
    protected $orderMode;

    /**
     * @var string
     */
    protected $orderDirection;

    /**
     * @var bool
     */
    private $keyed;

    /**
     * @return bool
     */
    public function isKeyed()
    {
        return $this->keyed;
    }

    /**
     * @param bool $keyed
     */
    public function setKeyed($keyed)
    {
        $this->keyed = $keyed;
    }

    /**
     * @param string $mode
     * @param string $direction
     */
    public function setOrder($mode, $direction = self::DIRECTION_ASC)
    {
        $this->orderMode = $mode;
        $this->orderDirection = $direction;
    }

    /**
     * @return int
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param int $interval
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;
    }

    /**
     * @return int
     */
    public function getMinDocCount()
    {
        return $this->minDocCount;
    }

    /**
     * @param int $minDocCount
     */
    public function setMinDocCount($minDocCount)
    {
        $this->minDocCount = $minDocCount;
    }

    /**
     * @return array
     */
    public function getExtendedBounds()
    {
        return $this->extendedBounds;
    }

    /**
     * @param int $min
     * @param int $max
     */
    public function setExtendedBounds($min = null, $max = null)
    {
        $bounds = array_filter(
            [
                'min' => $min,
                'max' => $max,
            ]
        );
        $this->extendedBounds = $bounds;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'histogram';
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        $out = array_filter(
            [
                'min_doc_count' => $this->getMinDocCount(),
                'interval' => $this->getInterval(),
                'extended_bounds' => $this->getExtendedBounds(),
            ]
        );

        if ($this->getField() && $this->getInterval()) {
            $out['field'] = $this->getField();
            $out['interval'] = $this->getInterval();
        } else {
            throw new \LogicException('Histogram aggregation must have field and interval set.');
        }

        if ($this->orderMode && $this->orderDirection) {
            $out['order'] = [
                $this->orderMode => $this->orderDirection,
            ];
        }

        if ($this->isKeyed()) {
            $out['keyed'] = $this->isKeyed();
        }

        return $out;
    }
}
