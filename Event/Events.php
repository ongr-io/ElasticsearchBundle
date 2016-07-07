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
    const BULK = 'es.pre_index';

    /**
     * The PRE_COMMIT event occurs before committing queries to ES
     */
    const PRE_COMMIT = 'es.pre_commit';

    /**
     * The POST_COMMIT event occurs after committing queries to ES
     */
    const POST_COMMIT = 'es.post_commit';

    /**
     * The PERSIST event occurs when passing a document to a managers persist method
     */
    const PERSIST = 'es.persist';
}
