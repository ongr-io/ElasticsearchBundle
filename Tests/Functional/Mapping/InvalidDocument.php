<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Mapping;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Document fixture with invalid embedded object.
 *
 * @ES\Document
 */
class InvalidDocument
{
    /**
     * @ES\Embedded(class="AcmeBarBundle:Product")
     */
    public $category;
}
