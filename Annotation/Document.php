<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation;

/**
 * Annotation to mark a class as an Elasticsearch document.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Document
{
    /**
     * @var bool
     */
    public $create = true;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $parent;

    /**
     * @var array
     */
    public $ttl;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var array
     */
    public $all;
}
