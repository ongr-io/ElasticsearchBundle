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
 * Contains all events thrown in the ONGRElasticsearchBundle
 */
final class Events
{
    /**
     * The PRE_PERSIST event occurs before convert to Array
     */
    const PRE_PERSIST = 'es.pre_persist';

    /**
     * The BULK event occurs before during the processing of bulk method
     */
    const BULK = 'es.bulk';

    /**
     * The PRE_COMMIT event occurs before committing queries to ES
     */
    const PRE_COMMIT = 'es.pre_commit';

    /**
     * The POST_COMMIT event occurs after committing queries to ES
     */
    const POST_COMMIT = 'es.post_commit';

    /**
     * The PRE_MANAGER_CREATE event occurs before manager is created, right after client is initiated.
     *  You can modify anything in the core elasticsearch-php client by this event.
     */
    const PRE_MANAGER_CREATE = 'es.pre_manager_create';

    /**
     * The POST_MANAGER_CREATE event occurs after manager is created.
     */
    const POST_MANAGER_CREATE = 'es.post_manager_create';
}
