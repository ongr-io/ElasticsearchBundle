<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Event;

/**
 * Static class for events names constants.
 */
final class Events
{
    /**
     * Event dispatched before any document is persisted.
     *
     * The event listener receives an ElasticsearchEvent instance.
     */
    const PRE_PERSIST = 'es.pre_persist';
}
