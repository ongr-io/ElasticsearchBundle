<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DSL\Aggregation;

use Ongr\ElasticsearchBundle\DSL\Aggregation\Type\BucketingTrait;

/**
 * Class representing RangeAggregation.
 */
class RangeAggregation extends AbstractAggregation
{
    use BucketingTrait;

    /**
     * @var array
     */
    private $ranges = [];

    /**
     * @var bool
     */
    private $keyed = false;

    /**
     * Sets if result buckets should be keyed.
     *
     * @param bool $keyed
     *
     * @return RangeAggregation
     */
    public function setKeyed($keyed)
    {
        $this->keyed = $keyed;

        return $this;
    }

    /**
     * Add range to aggregation.
     *
     * @param int|float|null $from
     * @param int|float|null $to
     * @param string         $key
     *
     * @return RangeAggregation
     */
    public function addRange($from = null, $to = null, $key = '')
    {
        $range = array_filter(
            [
                'from' => $from,
                'to' => $to,
            ]
        );

        if ($this->keyed && !empty($key)) {
            $range['key'] = $key;
        }

        $this->ranges[] = $range;

        return $this;
    }

    /**
     * Remove range from aggregation. Returns true on success.
     *
     * @param int|float|null $from
     * @param int|float|null $to
     *
     * @return bool
     */
    public function removeRange($from, $to)
    {
        foreach ($this->ranges as $key => $range) {
            if (array_diff_assoc(array_filter(['from' => $from, 'to' => $to]), $range) === []) {
                unset($this->ranges[$key]);

                return true;
            }
        }

        return false;
    }

    /**
     * Removes range by key.
     *
     * @param string $key Range key.
     *
     * @return bool
     */
    public function removeRangeByKey($key)
    {
        if ($this->keyed) {
            foreach ($this->ranges as $rangeKey => $range) {
                if (array_key_exists('key', $range) && $range['key'] === $key) {
                    unset($this->ranges[$rangeKey]);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        $data = [
            'keyed' => $this->keyed,
            'ranges' => array_values($this->ranges),
        ];

        if ($this->getField()) {
            $data['field'] = $this->getField();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'range';
    }
}
