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
 * Holds document event types.
 */
final class DocumentEvent
{
    /**
     * Pre create event constant.
     */
    const PRE_PERSIST = 'document.pre_persist';
}
