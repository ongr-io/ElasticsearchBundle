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

use ONGR\ElasticsearchBundle\Event\DocumentEvent;

/**
 * Annotation used to register lifecycle callbacks for methods.
 *
 * @Annotation
 * @Target("METHOD")
 */
class PrePersist
{
    /**
     * @var string
     */
    public $type = DocumentEvent::PRE_PERSIST;
}
