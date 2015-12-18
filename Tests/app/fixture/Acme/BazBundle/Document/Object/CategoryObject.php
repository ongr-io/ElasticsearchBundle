<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BazBundle\Document\Object;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Category document for testing.
 *
 * @ES\Object
 */
class CategoryObject
{
    /**
     * @var string Field without ESB annotation, should not be indexed.
     */
    public $withoutAnnotation;

    /**
     * @var string
     * @ES\Property(type="string", name="title", options={"index"="not_analyzed"})
     */
    public $title;
}
