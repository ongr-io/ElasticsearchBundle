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
     * Returns context type.
     *
     * @return string
     */
    public function getType()
    {
        return 'geo_location';
    }
}
