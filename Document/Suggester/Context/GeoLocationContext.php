<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Document\Suggester\Context;

/**
 * Abstract geo location context type for context suggester.
 */
class GeoLocationContext extends AbstractContext
{
    /**
     * @var array|string
     */
    private $location;

    /**
     * Returns location.
     *
     * @return array|string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets location.
     *
     * @param array|string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * {@inheritdoc}
     */
    public function getContextType()
    {
        return 'geo_location';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->getLocation();
    }
}
