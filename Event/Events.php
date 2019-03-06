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
     * The BULK event occurs before during the processing of bulk method
     */
    const BULK = 'ongr.es.event.bulk';

    /**
     * The PRE_COMMIT event occurs before committing queries to ES
     */
    const PRE_COMMIT = 'ongr.es.event.pre_commit';

    /**
     * The POST_COMMIT event occurs after committing queries to ES
     */
    const POST_COMMIT = 'ongr.es.event.post_commit';

    /**
     * The POST_CLIENT_CREATE event occurs after client is formed. It is still not build,
     * so you can modify or add another information to it. After this event the build() method is called.
     */
    const POST_CLIENT_CREATE = 'ongr.es.event.post_client_create';
}
