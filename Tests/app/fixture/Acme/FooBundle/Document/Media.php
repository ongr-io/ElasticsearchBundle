<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\FooBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\AbstractDocument;

/**
 * Testing document for representing media.
 * 
 * @ES\Document();
 */
class Media extends AbstractDocument
{
    /**
     * @var string
     * 
     * @ES\Property(name="media", type="string", index="not_analyzed")
     */
    public $url;
}
