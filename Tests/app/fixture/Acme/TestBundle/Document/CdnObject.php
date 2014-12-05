<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * CdnObject document for testing.
 *
 * @ES\Object
 */
class CdnObject
{
    /**
     * @var string
     *
     * @ES\Property(name="cdn_url", type="string")
     */
    public $cdn_url;
}
