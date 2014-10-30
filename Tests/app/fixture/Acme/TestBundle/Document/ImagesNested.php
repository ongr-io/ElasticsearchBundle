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
 * Images nested document for testing.
 *
 * @ES\Nested
 */
class ImagesNested
{
    /**
     * @var string
     *
     * @ES\Property(name="url", type="string")
     */
    public $url;

    /**
     * @var string
     *
     * @ES\Property(name="title", type="string", index="no")
     */
    public $title;

    /**
     * @var string
     *
     * @ES\Property(name="description", type="string", index="no")
     */
    public $description;

    /**
     * @var object
     *
     * @ES\Property(name="cdn", type="object", objectName="AcmeTestBundle:CdnObject")
     */
    public $cdn;
}
