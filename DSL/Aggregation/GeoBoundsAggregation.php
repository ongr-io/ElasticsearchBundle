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
 * Class representing geo bounds aggregation.
 */
class GeoBoundsAggregation extends AbstractAggregation
{
    use BucketingTrait;

    /**
     * @var bool
     */
    private $wrapLongitude = true;

    /**
     * @return bool
     */
    public function isWrapLongitude()
    {
        return $this->wrapLongitude;
    }

    /**
     * @param bool $wrapLongitude
     */
    public function setWrapLongitude($wrapLongitude)
    {
        $this->wrapLongitude = $wrapLongitude;
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        $data = [];
        if ($this->getField()) {
            $data['field'] = $this->getField();
        } else {
            throw new \LogicException('Geo bounds aggregation must have a field set.');
        }

        $data['wrap_longitude'] = $this->isWrapLongitude();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'geo_bounds';
    }
}
