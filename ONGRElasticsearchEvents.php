<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle;

/**
 * Contains all events thrown in the ONGRElasticsearchBundle
 */
final class ONGRElasticsearchEvents
{
    /**
     * The PRE_INDEX event occurs before index query
     */
    const PRE_INDEX = 'es.pre_index';

    /**
     * The PRE_CREATE event occurs before create query
     */
    const PRE_CREATE = 'es.pre_create';

    /**
     * The PRE_UPDATE event occurs before update query
     */
    const PRE_UPDATE = 'es.pre_update';

    /**
     * The PRE_DELETE event occurs before delete query
     */
    const PRE_DELETE = 'es.pre_delete';

    /**
     * The PRE_COMMIT event occurs before committing queries to ES
     */
    const PRE_COMMIT = 'es.pre_commit';
}
